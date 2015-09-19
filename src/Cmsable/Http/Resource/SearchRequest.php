<?php namespace Cmsable\Http\Resource;

use Illuminate\Http\Request;
use Cmsable\Http\Contracts\DecoratesRequest;
use Illuminate\Http\JsonResponse;
use Cmsable\Support\ReceivesContainerWhenResolved;
use Cmsable\Resource\Contracts\ReceivesDistributorWhenResolved;
use Cmsable\Support\HoldsContainer;
use Cmsable\Http\ReplicateRequest;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Routing\Redirector;
use Cmsable\Resource\UsesCurrentResource as UsesResource;

class SearchRequest extends Request implements DecoratesRequest,
                                               ReceivesContainerWhenResolved,
                                               ReceivesDistributorWhenResolved
{

    use HoldsContainer;
    use ReplicateRequest;
    use UsesResource;

    protected $cleaned;

    protected $model;

    protected $caster;

    /**
     * The redirector instance.
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'default';

    public function search(array $defaults=[])
    {

        $all = array_merge($defaults, $this->all());
        $resourceName = $this->resourceName();

        $modelClass = $this->container->make('Cmsable\Resource\Contracts\ModelClassFinder')->modelClass($this->resourceName());
        $criteria = $this->container->make('versatile.criteria-builder')->criteria($modelClass, $all);

        $keys = $this->container->make('versatile.model-presenter')->keys($modelClass);

        $criteria->setResource($resourceName);

        $searchFactory = $this->container->make('versatile.search-factory');

        return $searchFactory->search($criteria)->withKey($keys);
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if ($this->ajax() || $this->wantsJson())
        {
            return new JsonResponse($errors, 422);
        }

        return $this->redirector->to($this->getRedirectUrl())
                                        ->withInput($this->except($this->dontFlash))
                                        ->withErrors($errors, $this->errorBag);
    }

    protected function createValidatorInstance()
    {
        return $this->distributor->validator($this->resourceName());
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect)
        {
            return $url->to($this->redirect);
        }
        elseif ($this->redirectRoute)
        {
            return $url->route($this->redirectRoute);
        }
        elseif ($this->redirectAction)
        {
            return $url->action($this->redirectAction);
        }

        return $url->previous();
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return array
     */
    protected function formatErrors(ValidationException $exception)
    {
        return $exception->errors()->getMessages();
    }

    protected function &performCasting(array $parameters)
    {
        $casted = $this->caster()->castInput($parameters);
        $this->publish('input.casted', [&$casted]);
        return $casted;
    }

    protected function caster()
    {
        if (!$this->caster) {
            $this->caster = $this->container->make('XType\Casting\Contracts\InputCaster');
        }
        return $this->caster;
    }

    /**
     * Set the Redirector instance.
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return self
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    public function fireAction($action, $params)
    {
       $eventName = $this->eventName($this->resourceName().".$action");
       return $this->fire($eventName, $params);
    }

    public function withConfirmations($with=true)
    {
        $this->caster()->withConfirmations($with);
        return $this;
    }

}