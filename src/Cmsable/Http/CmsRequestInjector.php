<?php namespace Cmsable\Http;

use Closure;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Cmsable\Cms\Application as CmsApplication;

class CmsRequestInjector implements Middleware
{

    public $requestEventName = 'cmsable::request-replaced';

    public $pathSettedEventName = 'cmsable::cms-path-setted';

    public $scopeChangedEventName = 'cmsable::treescope-changed';

    protected $app;

    protected $cmsApplication;

    protected $cmsPathCreator;

    public function __construct(Application $app,
                                CmsRequestConverter $requestConverter,
                                CmsPathCreatorInterface $pathCreator)
    {
        $this->app = $app;
        $this->requestConverter = $requestConverter;
        $this->cmsPathCreator = $pathCreator;
    }

    /**
     * Handle the given request and get the response.
     *
     * Provides compatibility with BrowserKit functional testing.
     *
     * @implements HttpKernelInterface::handle
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $request = $this->requestConverter->toCmsRequest($request);

        $request->provideCmsPath(function($request){
            $this->attachCmsPath($request);
        });

        $this->injectRequest($request);

        $this->app['events']->fire($this->requestEventName,[$request, $this]);

        return $next($request);

    }

    protected function attachCmsPath(CmsRequest $request){

        $cmsPath = $this->cmsPathCreator->createFromRequest($request);

        $request->setCmsPath($cmsPath);

        if($request->originalPath() != $request->path()){
            $this->app['events']->fire($this->pathSettedEventName,[$cmsPath]);
        }

        $this->app['events']->fire($this->scopeChangedEventName, [$cmsPath->getTreeScope()]);

    }

    protected function injectRequest(CmsRequest $request)
    {
        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');
    }
}