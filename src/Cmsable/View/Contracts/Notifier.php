<?php


namespace Cmsable\View\Contracts;

/**
 * This interface is used to send messages from typically your controllers
 * In case of normal HTTP requests the messages will be typically flashed to
 * session.
 **/
interface Notifier
{

    /**
     * Display (flash) an info message
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function info($message, array $parameters=[]);

    /**
     * Display (flash) a success message
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function success($message, array $parameters=[]);

    /**
     * Display (flash) an error message
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function error($message, array $parameters=[]);

    /**
     * Display (flash) a warning message
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function warning($message, array $parameters=[]);

}