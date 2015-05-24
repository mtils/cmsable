<?php namespace Cmsable\PageType;

use InvalidArgumentException;
use Cmsable\Translation\TranslatorInterface as Lang;

class TranslationNamer
{

    protected $lang;

    public $langKey = 'ems::pagetypes';

    public function __construct(Lang $lang)
    {
        $this->lang = $lang;
    }

    public function setNames(RepositoryInterface $repo){

        foreach ($repo->all() as $pageType) {

            if (!$pageType->singularName()) {
                $pageType->setSingularName($this->getTitle($pageType, 1));
                $pageType->setPluralName($this->getTitle($pageType, 2));
            }

            if (!$pageType->description()) {
                $pageType->setDescription($this->getDescription($pageType));
            }

        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $class The class or an object of it
     * @param int $quantity (optional) The quantity (for singular/plural)
     * @return string A readable title of this object
     **/
    public function getTitle($pageType, $quantity=1)
    {
        return $this->lang->choice($this->getLangKey($pageType).'.title', $quantity);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $class The class or an object of it
     * @param int $quantity (optional) The quantity (for singular/plural)
     * @return string A readable title of this object
     **/
    public function getDescription(PageType $pageType)
    {
        return $this->lang->get($this->getLangKey($pageType).'.description');
    }

    protected function getLangKey(PageType $pageType)
    {
        return $this->langKey . '.' .str_replace('.', '/', $pageType->getId());
    }

}
