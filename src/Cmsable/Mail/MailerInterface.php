<?php namespace Cmsable\Mail;

/**
 * This mailer has almost the same signature as the laravel mailer. The main
 * difference is that you dont have to pass a closure to send mails. This makes
 * the code much more readable. However you can pass a callable if you like but
 * mostly the only thing you call is ->to and ->subject.
 *
 * The mailer should be injected into controllers and not replace the original
 * laravel mailer
 **/
interface MailerInterface{

    /**
     * Sets the recipient or the recipients for the message
     * 
     * @example Mailer::to('foo@somewhere.com')->send('template',$data)
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient);

    /**
     * Sends a message with plan text. $data has to contain the subject
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function plain($view, array $data, $callback=null);

    /**
     * Sends a html mail. $data has to contain the subject
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function send($view, array $data, $callback=null);

    /**
     * Sends a mail via queing
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue Der name of the queue
     **/
    public function queue($view, array $data, $callback=null, $queue=null);

    /**
     * Sends the mail later
     *
     * @param int $delay Delay in seconds
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue The name of the queue
     **/
    public function later($delay, $view, $data, $callback=null, $queue=null);

}