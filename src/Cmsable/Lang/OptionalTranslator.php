<?php namespace Cmsable\Lang;

use Symfony\Component\Translation\TranslatorInterface;

class OptionalTranslator
{

    protected static $translator;

    protected static $translatorProvider;

    public static function guess($key, array $replace = array(), $locale = null)
    {

        if (static::isLangKey($key)) {
            return static::translator()->get($key, $replace, $locale);
        }

        return $key;

    }

    public static function translator()
    {

        if (!static::$translator && static::$translatorProvider) {
            $provider = static::$translatorProvider;
            static::setTranslator($provider());
        }

        return static::$translator;
    }

    public static function setTranslator(TranslatorInterface $translator)
    {
        static::$translator = $translator;
    }

    public static function isLangKey($key)
    {
        return (str_contains($key,'.') && preg_match('/^[\pL\pM\pN_\-\.:]+$/u', $key));
    }

    public static function provideTranslator(callable $provider)
    {
        static::$translatorProvider = $provider;
    }

    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::translator(), $method], $params);
    }

}