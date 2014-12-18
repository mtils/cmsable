<?php namespace Cmsable\Http;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Cmsable\Cms\Application AS CmsApplication;

class CmsRequestInjector implements HttpKernelInterface, TerminableInterface{

    public $requestEventName = 'cmsable::request-replaced';

    protected $app;

    protected $cmsApplication;

    public function __construct(HttpKernelInterface $app, CmsApplication $cmsApplication)
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
     * @param  int   $type
     * @param  bool  $catch
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {

        if(!$this->app->runningInConsole()){
            $request = $this->cmsApplication->_updateCmsRequest($request);
            $this->app['events']->fire($this->requestEventName,[$request, $this]);
        }

        return $this->app->handle($request, $type, $catch);

    }

    public function terminate(Request $request, Response $response)
    {
        $this->app->terminate($request, $response);
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