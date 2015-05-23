<?php namespace Cmsable\View;

interface TextParserInterface
{

    /**
     * Parses string text and replaces all occurences of placeholders. The parser
     * should not remove any unknown placeholders. Other parsers could handle
     * them after parsing with this parser. Remove all unknown placeholders
     * in purge
     *
     * @param string $text
     * @param array $data The view variables
     * @return string
     **/
    public function parse($text, array $data);

    /**
     * Clean all unknown placeholders from text (replace with '')
     *
     * @param string $text
     * @return string The purged text
     **/
    public function purge($text);

}