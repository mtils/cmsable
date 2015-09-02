<?php


namespace Cmsable\View;

use Cmsable\View\Contracts\Notifier;
use Krucas\Notification\Notification;

/**
 * This is a flash-only notifier which uses edvinaskrucas/notification
 **/
class NotificationNotifier implements Notifier
{

    /**
     * @var \Krucas\Notification\Notification
     **/
    protected $notification;


    /**
     * @param \Krucas\Notification\Notification $notification
     **/
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function info($message, array $parameters=[])
    {
        $this->notification->info($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function success($message, array $parameters=[])
    {
        $this->notification->success($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function error($message, array $parameters=[])
    {
        $this->notification->error($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $parameters (optional)
     * @return void
     **/
    public function warning($message, array $parameters=[])
    {
        $this->notification->warning($message);
    }

}