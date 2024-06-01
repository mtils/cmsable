<?php
/**
 *
 * Created by mtils on 01.06.2024 at 11:33.
 **/

namespace Cmsable\View;

use Cmsable\View\Contracts\Notifier;
use Illuminate\Http\Request;

use Illuminate\Session\Store;
use TypeError;

use function call_user_func;

class LaravelSessionNotifier implements Notifier
{

    protected string $prefix = 'cmsable.notifier.';

    /**
     * @var callable
     */
    protected $requestProvider;

    /**
     * Pass a callable returning the current request.
     *
     * @param callable $requestProvider
     */
    public function __construct(callable $requestProvider)
    {
        $this->requestProvider = $requestProvider;
    }

    public function info($message, array $parameters = []): void
    {
        $this->flash(__METHOD__, $message, $parameters);
    }

    public function success($message, array $parameters = []): void
    {
        $this->flash(__METHOD__, $message, $parameters);
    }

    public function error($message, array $parameters = []): void
    {
        $this->flash(__METHOD__, $message, $parameters);
    }

    public function warning($message, array $parameters = []): void
    {
        $this->flash(__METHOD__, $message, $parameters);
    }

    /**
     * @param string $level
     * @param Store|null $store
     * @return array{level:string,message:string,parameters:[]}[]
     */
    public function messagesFor(string $level, ?Store $store=null) : array
    {
        $store = $store ?: $this->session();
        $key = $this->key($level);
        if ($store->has($key)) {
            return $store->get($key);
        }
        return [];
    }

    /**
     * @param Store|null $store
     * @return array{level:string,message:string,parameters:[]}[]
     */
    public function messages(?Store $store) : array
    {
        $all = [];
        foreach (['info', 'success', 'error', 'warning'] as $level) {
            foreach($this->messagesFor($level, $store) as $message) {
                $all[] = $message;
            }
        }
        return $all;
    }

    protected function flash(string $level, $message, array $parameters = []) : void
    {
        $key = $this->key($level);
        $session = $this->session();
        $messages = $session->get($key);
        $existed = is_array($messages);
        if (!$existed) {
            $messages = [];
        }
        $messages[] = [
            'level' => $level,
            'message' => $message,
            'parameters' => $parameters
        ];
        if ($existed) {
            $session->put($key, $messages);
            return;
        }
        $session->flash($key, $messages);
    }

    protected function key(string $level) : string
    {
        return $this->prefix . $level;
    }

    /**
     * @return Store
     */
    protected function session() : Store
    {
        $session = $this->request()->session();
        if (!$session instanceof Store) {
            throw new TypeError('The request does contain no valid Session\Store');
        }
        return $session;
    }

    /**
     * @return Request
     */
    protected function request() : Request
    {
        $request = call_user_func($this->requestProvider);
        if (!$request instanceof Request) {
            throw new TypeError('The assigned callable has to return a laravel request');
        }
        return $request;
    }
}