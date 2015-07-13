<?php namespace Cmsable\Http;

use Closure;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Routing\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Cmsable\Cms\Application AS CmsApplication;

class CmsRequestInjector implements Middleware
{

    public $requestEventName = 'cmsable::request-replaced';

    protected $app;

    protected $cmsApplication;

    public function __construct(Application $app, CmsApplication $cmsApplication)
    {
        $this->app = $app;
        $this->cmsApplication = $cmsApplication;
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

        $request = $this->cmsApplication->_updateCmsRequest($request);
        $this->app['events']->fire($this->requestEventName,[$request, $this]);

        return $next($request);

    }

    protected function toCmsRequest(Request $request){

        $cmsRequest = (new CmsRequest)->duplicate(

            $request->query->all(), $request->request->all(), $request->attributes->all(),

            $request->cookies->all(), $request->files->all(), $request->server->all()
        );

        $cmsRequest->setEventDispatcher($this->app['events']);

        return $cmsRequest;

    }

}