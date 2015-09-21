<?php


namespace Cmsable\Http;


use Illuminate\Contracts\Routing\Middleware;
use Closure;
use ArrayAccess;
use Signal\NamedEvent\BusHolderTrait;

/**
 * The ContentTypeMorpher allows to render different content types like pdfs,
 * XHR Stuff, Excel, Images, ... from outside of your controller.
 *
 * So instead of make the content type detection inside your controller and
 * fill it with if or switch statements you can hook into the reponse generation
 * and morph the generated view/response to something different. Laravel totally
 * defers view rendering viw Renderable and __toString implementation. This has
 * the benefit that the view can be seen as a container until it has to render.
 *
 * So just assign a callable for a content-type:
 *
 * $handler = function($response, $contentType){
 *     return new Reponse(base64encode($response->getContent()));
 * }
 *
 * Event::listen('cmsable::responding.application/pdf', $handler);
 *
 * The content type is detected via the content type detector callable:
 *
 * $detector = function($contentTypeSwitcher) {
 *     return Request::header('Accept');
 * }
 *
 **/
class ContentTypeMorpher implements Middleware
{

    use BusHolderTrait;

    public $defaultContentType = 'text/html';

    public $eventNamespace = 'cmsable';

    /**
     * @var callable
     **/
    protected $contentTypeDetector;

    /**
     * {@inheritdoc}
     * (Use the switcher as middleware)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $response = $next($request);

        if ($morpedResponse = $this->morphResponseIfNeeded($response)) {
            return $morpedResponse;
        }

        return $response;

    }

    /**
     * Assign a callable which will detect the $contentType string
     * The callable will be called with this ($response, $this)
     *
     * @param callable $detector
     * @return self
     **/
    public function detectContentTypeBy(callable $detector)
    {
        $this->contentTypeDetector = $detector;
        return $this;
    }

    /**
     * Does the actual work. Looks if a morpher is set and call it if needed
     *
     * @param mixed $response
     * @return null|$response
     **/
    protected function morphResponseIfNeeded($response)
    {

        $contentType = $this->detectContentType($response);

        if ($newResponse = $this->getFromListeners($contentType, $response)) {
            return $newResponse;
        }

    }

    /**
     * Detects the content type
     *
     * @param mixed $response
     * @return string
     **/
    protected function detectContentType($response)
    {
        if (!$this->contentTypeDetector) {
            return $this->defaultContentType;
        }

        if ($contentType = call_user_func($this->contentTypeDetector, $response, $this)) {
            return $contentType;
        }

        return $this->defaultContentType;
    }

    /**
     * Builds the event name
     *
     * @param string $contentType
     * @return string
     **/
    protected function eventName($contentType)
    {
        return $this->eventNamespace . "::responding.$contentType";
    }

    /**
     * Fires the event and returns the result if there were some
     *
     * @param string $contentType
     * @param \Illuminate\Http\Response $response
     * @return \Illuminate\Http\Response|null
     **/
    protected function getFromListeners($contentType, $response)
    {
        $eventName = $this->eventName($contentType);
        return $this->fire($eventName, [$response, $this], true);
    }

}