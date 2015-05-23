<?php namespace Cmsable\Translation;

use Symfony\Component\Translation\TranslatorInterface as SymfonyInterface;

/**
 * This is a copy of the most used methods of laravels translator which extends
 * the symfony translator.
 * This interface ist mostly used to inject it into your controller and overwrite
 * any translation keys from outside like your pagetype or controller creator
 */
interface TranslatorInterface extends SymfonyInterface
{

    /**
     * Determine if a translation exists.
     *
     * @param  string  $key
     * @param  string  $locale
     * @return bool
     **/
    public function has($key, $locale = null);

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     **/
    public function get($key, array $replace = array(), $locale = null);

    /**
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  int     $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     **/
    public function choice($key, $number, array $replace = array(), $locale = null);

}