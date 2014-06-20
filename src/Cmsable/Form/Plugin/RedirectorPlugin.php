<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use FormObject\Field\SelectOneField;
use Event;
use App;

class RedirectorPlugin extends Plugin{

    public function modifyFormFields(FieldList $fields){

        $fields('main')->offsetUnset('content');

        $internalExternal = SelectOneField::create('redirect_type','Art der Weiterleitung')
                ->setSrc(
                    array(
                        'internal'=> 'Auf Interne Seite',
                        'external'=> 'Auf externe Seite'
                    )
                );
        $fields('main')->push($internalExternal);

        
    }
}