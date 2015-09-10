<?php


namespace Cmsable\Http;


use Illuminate\Contracts\Routing\Middleware;
use Closure;
use ArrayAccess;

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
 * ContentTypeMorpher::on('application/pdf', $handler);
 *
 * The content type is detected via the content type detector callable:
 *
 * $detector = function($contentTypeSwitcher) {
 *     return Request::header('Accept');
 * }
 *
 **/
class ContentTypeMorpher implements Middleware, ArrayAccess
{

    public $defaultContentType = 'text/html';

    /**
     * @var callable
     **/
    protected $contentTypeDetector;

    /**
     * @var array
     **/
    protected $morphers=[];

    /**
     * @var callable
     **/
    protected $morpherProvider;

    /**
     * @var bool
     **/
    protected $wasFilledByProvider = false;

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
     * Assign a morpher for a contentType.
     *
     * @param string $contentType
     * @param callable $morpher
     * @return self
     **/
    public function on($contentType, callable $morpher)
    {
        $this->morphers[$contentType] = $morpher;
        return $this;
    }

    /**
     * Check if a morpher exists for $contentType
     *
     * @param string $contentType
     * @return bool
     **/
    public function offsetExists($contentType)
    {
        $this->fillByProviderIfNotDone();
        return isset($this->morphers[$contentType]);
    }

    /**
     * Return morpher for $contentType
     *
     * @param string $contentType
     * @return callable|null
     **/
    public function offsetGet($contentType)
    {
        $this->fillByProviderIfNotDone();
        return $this->morphers[$contentType];
    }

    /**
     * Set a morpher for $contentType
     *
     * @param string $contentType
     * @param callable $morpher
     * @return void
     * @see self::on()
     **/
    public function offsetSet($contentType, $morpher)
    {
        $this->on($contentType, $morpher);
    }

    /**
     * Unset the morpher of $contentType
     *
     * @param string $contentType
     * @return void
     **/
    public function offsetUnset($contentType)
    {
        unset($this->morphers[$contentType]);
    }

    /**
     * Returns all morphers as a [$contentType=>$morpher] array
     *
     * @return array
     **/
    public function morphers()
    {
        $this->fillByProviderIfNotDone();
        return $this->morphers;
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
     * An additional hook to fill the morphers. Makes it easier to assign them
     * before needed (at the end of the application cycle).
     * The callable will be called with ($this)
     *
     * @param callable $provider
     * @return self
     **/
    public function provideMorphers(callable $provider)
    {
        $this->morpherProvider = $provider;
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

        if (!$this->offsetExists($contentType)) {
            return;
        }

        return call_user_func($this->offsetGet($contentType), $response, $this);

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
     * Fills the morphers to allow late instanciation
     *
     * @return void
     **/
    protected function fillByProviderIfNotDone()
    {

        if ($this->wasFilledByProvider || !$this->morpherProvider) {
            return;
        }

        call_user_func($this->morpherProvider, $this);
        $this->wasFilledByProvider = true;
    }

}