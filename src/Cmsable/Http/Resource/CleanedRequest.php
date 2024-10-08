<?php namespace Cmsable\Http\Resource;


use Illuminate\Http\Request;
use Cmsable\Http\Contracts\DecoratesRequest;
use Illuminate\Http\JsonResponse;
use Cmsable\Support\ReceivesContainerWhenResolved;
use Cmsable\Resource\Contracts\ReceivesDistributorWhenResolved;
use Cmsable\Support\HoldsContainer;
use Cmsable\Http\ReplicateRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Redirector;
use Cmsable\Resource\UsesCurrentResource as UsesResource;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CleanedRequest extends Request implements DecoratesRequest,
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
     * @var Redirector
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

    /**
     * The input keys that should not be flashed on redirect.
     *
     * @var array
     */
    protected $dontFlash = ['password', 'password_confirmation'];

    protected $casted;

    public function cleaned($key=null, $default=null)
    {

        if ($this->casted === null) {

            $all = $this->all();

            $this->casted = $this->performCasting($this->all());

            $this->performValidation($this->casted, $this->model);

        }

        if ($key != null) {
            return Arr::get($this->casted, $key, $default);
        }

        return $this->casted;
    }

    public function model($model=null)
    {

        if ($model === null) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }

    protected function performValidation(array $parameters, $model=null)
    {
        $validator = $this->createValidatorInstance();

        try {
            $validator->validateOrFail($parameters, $model);
        } catch (ValidationException $e) {
            $this->failedValidation($e);
        }

    }

    /**
     * Handle a failed validation attempt.
     *
     * @param ValidationException $exception
     * @return mixed
     */
    protected function failedValidation(ValidationException $exception)
    {
        throw new HttpResponseException($this->response(
            $this->formatErrors($exception)
        ));
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array  $errors
     * @return Response
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
     * @param ValidationException $exception
     * @return array
     */
    protected function formatErrors(ValidationException $exception)
    {
        return $exception->errors();
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
     * @param Redirector $redirector
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
        if ($with) {
            $this->caster = $this->caster()->with(['no_confirmations']);
            return $this;
        }
        $this->caster = $this->caster()->with(['!no_confirmations']);
        return $this;
    }

}