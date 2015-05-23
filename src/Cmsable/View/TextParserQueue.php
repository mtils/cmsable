<?php namespace Cmsable\View;

use Signal\NamedEvent\BusHolderTrait;

class TextParserQueue implements TextParserInterface
{

    use BusHolderTrait;

    /**
     * All assigned parsers
     *
     * @var array
     **/
    protected $parsers = [];

    /**
     * An spl_object_hash array to ignore already added parsers
     *
     * @var array
     **/
    protected $parserIds = [];

    /**
     * Add a new parser to the render queue
     *
     * @param \Cmsable\View\TextParserInterface $parser
     * @return self
     **/
    public function add(TextParserInterface $parser)
    {

        $objectHash = spl_object_hash($parser);

        if (isset($this->parserIds[$objectHash])) {
            return $this;
        }

        $this->parsers[] = $parser;

        $this->parserIds[$objectHash] = true;

        return $this;
    }

    /**
     * Remove a parser from the render queue
     *
     * @param \Cmsable\View\TextParserInterface $parser
     * @return self
     **/
    public function remove(TextParserInterface $parser)
    {
        $this->parsers = array_filter($this->parsers, function($known) use ($parser) {
            return ($known !== $parser);
        });
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param array $data The view variables
     * @return string
     **/
    public function parse($text, array $data)
    {
        foreach ($this->parsers() as $parser) {
            $text = $parser->parse($text, $data);
        }
        return $this->purge($text);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @return string The purged text
     **/
    public function purge($text)
    {
        foreach ($this->parsers as $parser) {
            $text = $parser->purge($text);
        }
        return $text;
    }

    public function parsers()
    {
        $this->fireOnce('cmsable.text-parser-load', [$this]);
        return $this->parsers;
    }

}