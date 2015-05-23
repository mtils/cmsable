<?php namespace Cmsable\Mail;;

use BadMethodCallException;
use RuntimeException;

use Illuminate\Mail\Mailer as IlluminateMailer;
use Illuminate\Mail\Message;
use Illuminate\Contracts\Config\Repository as Config;
use Cmsable\View\TextParserInterface as TextParser;

/**
 * This mailer class is handy to inject into your controllers. If you have
 * a PageType that allows overwrites of email content you can overwrite all
 * settings in this mailer.
 *
 * You can assign view variables with overwrite($key,$value) or the template,
 * add recipients and so on. The directly setted variables by your controller
 * will then be ignored
 **/
class Mailer implements MailerInterface
{

    /**
     * @var \Illuminate\Mail\Mailer
     **/
    protected $laravelMailer;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     **/
    protected $config;

    /**
     * @var \Cmsable\View\TextParserInterface
     **/
    protected $textParser;

    /**
     * @var array
     **/
    protected $temporaryTo=[];

    /**
     * @var array
     **/
    protected $overwrittenTo=[];

    /**
     * @var string
     **/
    protected $overwrittenView = '';

    /**
     * @var array
     **/
    protected $overwrittenData = [];

    /**
     * @var array
     **/
    protected $parseKeys = ['subject', 'body', 'content'];

    /**
     * @param \Illuminate\Mail\Mailer $laravelMailer
     * @param \Cmsable\View\TextParserInterface $textParser
     * @param \Illuminate\Contracts\Config\Repository $config
     **/
    public function __construct(IlluminateMailer $laravelMailer,
                                TextParser $textParser,
                                Config $config){

        $this->laravelMailer = $laravelMailer;
        $this->textParser = $textParser;
        $this->config = $config;

        $this->textParser->parsers();$this->textParser->parsers();$this->textParser->parsers();

    }

    /**
     * {@inheritdoc}
     * 
     * @example Mailer::to('foo@somewhere.com')->send('template',$data)
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient)
    {
        $this->temporaryTo = func_num_args() > 1 ? func_get_args() : (array)$recipient;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function plain($view, array $data, $callback=null)
    {
        return $this->send(['text'=>$view], $data, $callback);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function send($view, array $data, $callback=null)
    {

        $recipients = $this->finalRecipients($this->flushRecipients($callback));

        $view = $this->finalView($view);

        $data = $this->parseTexts($this->finalData($data));

        $messageBuilder = $this->createBuilder($recipients, $data, $callback);

        return $this->laravelMailer->send($view, $data, $messageBuilder->builder());

    }


    /**
     * Return all data keys which will be parsed by a text parser
     *
     * @return array
     **/
    public function getParseKeys()
    {
        return $this->parseKeys;
    }

    /**
     * All keys of this array will be parsed by a text parser
     *
     * @param array $keys
     * @return self
     **/
    public function useParseKeys(array $keys)
    {
        $this->parseKeys = $keys;
        return $this;
    }

    /**
     * @return array
     **/
    public function overwrittenTo()
    {
        return $this->overwrittenTo;
    }

    /**
     * @param string|array $to
     * @return self
     **/
    public function overwriteTo($to)
    {
        $this->overwrittenTo = func_num_args() > 1 ? func_get_args() : (array)$to;
        return $this;
    }

    /**
     * The overwritten template
     *
     * @return string
     **/
    public function overwrittenView()
    {
        return $this->overwrittenView;
    }

    /**
     * Overwrite the view
     *
     * @param string $view
     * @return self
     **/
    public function overwriteView($view)
    {
        $this->overwrittenView = $view;
        return $this;
    }

    /**
     * Overwrite one or more view variables
     *
     * @param string|array $key
     * @param mixed $value (optional)
     * @return self
     **/
    public function overwrite($key, $value=null)
    {
        if (!is_array($key)) {
            $this->overwrittenData[$key] = $value;
            return $this;
        }

        foreach ($key as $k=>$v) {
            $this->overwrite($k, $v);
        }

        return $this;
    }

    /**
     * Get an overwritten view variable
     *
     * @param string $key
     * @return mixed
     **/
    public function getOverwrite($key)
    {
        if (isset($this->overwrittenData[$key])) {
            return $this->overwrittenData[$key];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue Der name of the queue
     **/
    public function queue($view, array $data, $callback=null, $queue=null)
    {
        throw new BadMethodCallException('Not implemented right now');
    }

    /**
     * {@inheritdoc}
     *
     * @param int $delay Delay in seconds
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue The name of the queue
     **/
    public function later($delay, $view, $data, $callback=null, $queue=null){
        throw new BadMethodCallException('Not implemented right now');
    }

    /**
     * Returns only the overwritten recipients or if non set the passed ones
     *
     * @param array $passedTo
     * @return array
     **/
    protected function finalRecipients($passedTo)
    {
        if (!$this->overwrittenTo) {
            return $passedTo;
        }

        $overwrittenTo = $this->overwrittenTo;
        $this->overwrittenTo = [];
        return $overwrittenTo;
    }

    /**
     * Returns the overwritten view if one set, otherwise the passed one
     *
     * @param string|array $passedView
     * @return string|array
     **/
    protected function finalView($passedView)
    {

        if (!$this->overwrittenView ) {
            return $passedView;
        }

        $overwrittenView = $this->overwrittenView;
        $this->overwrittenView = '';

        if (!is_array($overwrittenView) && !is_array($passedView)) {
            return $overwrittenView;
        }

        if (!is_array($overwrittenView) || !is_array($passedView)) {
            throw new RuntimeException("Overwritten view and passed can not be merged");
        }

        foreach ($overwrittenView as $key=>$value) {
            $passedView[$key] = $value;
        }

        return $passedView;

    }

    /**
     * Merges the passed view data with the assigned one
     *
     * @param array $passedData
     * @return array
     **/
    protected function finalData($passedData)
    {

        $overwrittenData = $this->overwrittenData;
        $this->overwrittenData = [];

        foreach ($overwrittenData as $key=>$value) {
            $passedData[$key] = $value;
        }

        return $passedData;

    }

    /**
     * Returns the recipients and clears em
     *
     * @param callable $closure
     * @return array
     **/
    protected function flushRecipients($closure)
    {
        $recipients = (array)$this->temporaryTo;
        $this->temporaryTo = null;

        if(!$recipients && !is_callable($closure)){
            throw new BadMethodCallException('Recipient not determinable: Neither a recipient was set by to() nor a callable was passed');
        }

        return $recipients;
    }

    /**
     * Create the pseudo closure creator
     *
     * @param array $recipients
     * @param array $data
     * @param callable|null $callback
     * @return \Cmsable\Mail\MessageBuilder
     **/
    protected function createBuilder(array $recipients, $data, $callback)
    {
        $messageBuilder = new MessageBuilder($recipients, $data, $callback);

        if ($developerTo = $this->config->get('mail.overwrite_to')) {
            $messageBuilder->setOverwriteTo($developerTo);
        }

        return $messageBuilder;
    }

    /**
     * Parse all keys that have to be parsed by text parser
     *
     * @param array $data
     * @return array
     **/
    protected function parseTexts(array $data)
    {
        foreach ($this->parseKeys as $key) {

            if (!isset($data[$key]) || !is_string($data[$key])) {
                continue;
            }

            $data[$key] = $this->textParser->parse($data[$key], $data);

        }

        return $data;
    }


    /**
     * Defer all unknown calls to laravel mailer
     *
     * @param string $method
     * @param array $params
     * @return mixed
     **/
    public function __call($method, $params){
        return call_user_func_array([$this->laravelMailer, $method], $params);
    }

}