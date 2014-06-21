<?php namespace Cmsable\Form;

use FormObject\Field\SelectOneField;
use Collection\Map\Extractor;
use CMS;
use PageForm;

class AdminPageForm extends PageForm{

    protected function createPageTypeField(){
        return SelectOneField::create('page_type')
                               ->setTitle(trans('cmsable::pagetypes.pagetype'))
                               ->setSrc(CMS::pageTypes()->all('admin'),
                                        new Extractor('getId()', 'singularName()'));
    }

}