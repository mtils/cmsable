<?php namespace Cmsable\Translation;

use Illuminate\Translation\Translator as IlluminateTranslator;

class Translator implements TranslatorInterface
{

    /**
     * @var \Illuminate\Translation\Translator
     **/
    protected $lang;

    /**
     * @var array
     **/
    protected $overwrites = [];

    public function __construct(IlluminateTranslator $translator)
    {
        $this->lang = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $key
     * @param  string  $locale
     * @return bool
     */
    public function has($key, $locale = null)
    {
        return $this->lang->has($key, $locale);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null)
    {
        return $this->lang->get($key, $replace, $locale);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $key
     * @param  int     $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = array(), $locale = null)
    {
        return $this->lang->choice($key, $number, $replace, $locale);
    }

    /**
     * Overwrite one or more lang keys
     *
     * @param string|array $key
     * @param mixed $message (optional)
     * @return self
     **/
    public function overwrite($key, $message=null)
    {
        if (!is_array($key)) {
            $this->overwrites[$key] = $message;
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
        if (isset($this->overwrites[$key])) {
            return $this->overwrites[$key];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     *
     * @api
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->get($id, $parameters, $locale);
    }

    /**
     * {@inheritdoc}
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     *
     * @api
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->choice($id, $number, $parameters, $locale);
    }

    /**
     * {@inheritdoc}
     *
     * @return string The locale
     *
     * @api
     */
    public function getLocale()
    {
        return $this->lang->getLocale();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale The locale
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @api
     */
    public function setLocale($locale)
    {
        return $this->lang->setLocale($locale);
    }

}