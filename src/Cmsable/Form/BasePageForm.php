<?php namespace Cmsable\Form;

use Validator;
use FormObject\Form;
use FormObject\FieldList;
use FormObject\Field;
use FormObject\Field\TextField;
use FormObject\Field\HiddenField;
use FormObject\Field\Action;
use FormObject\Field\CheckboxField;
use FormObject\Field\BooleanRadioField;
use FormObject\Field\SelectOneField;
use FormObject\Field\SelectFlagsField;
use Collection\Map\Extractor;
use CMS;
use PageType;
use Config;
use ReflectionClass;
use URL;
use Cmsable\Resource\Contracts\ResourceForm;

class UrlSegmentField extends TextField{
    public $pathPrefix = '/';
}

class BasePageForm extends Form implements ResourceForm
{

    public $validationRules = [
        'menu_title' => 'required|min:3|max:255',
        'url_segment' => 'required|min:1|max:255|url_segment|unique_segment_of:parent_id,id|no_manual_route:parent_id',
        'title' => 'required|min:3|max:255',
        'page_type' => 'required',
        'parent_id' => 'required'
    ];

    protected $routeScope = 'default';

    public function getName(){
        return 'page-form';
    }

    public function resourceName()
    {
        return 'sitetree';
    }

    public function getRouteScope(){
        return $this->routeScope;
    }

    public function setRouteScope($scope){
        $this->routeScope = $scope;
        return $this;
    }

    public function createFields(){

        $parentFields = parent::createFields();

//         $parentFields->setSwitchable(true);

        $mainFields = new FieldList('main',trans('cmsable::forms.page-form.main'));
        $mainFields->setClassName('CmsMainFields');
        $mainFields->setSwitchable(TRUE);

        $mainFields->push(TextField::create('title')
                                ->setTitle(trans('cmsable::models.page.fields.title')));

        $mainFields->push(TextField::create('menu_title')
                                 ->setTitle(trans('cmsable::models.page.fields.menu_title')));

        $mainFields->push(UrlSegmentField::create('url_segment')
                          ->setTitle(trans('cmsable::models.page.fields.url_segment')));


        $mainFields->push(TextField::create('content')
                                ->setTitle(trans('cmsable::models.page.fields.content'))
                                ->setMultiLine(TRUE)
                                ->setValue(''));

        $mainFields->push(HiddenField::create('id'));
        $mainFields->push(HiddenField::create('parent_id'));

        $parentFields->push($mainFields)->push($this->getSettingFields());

        return $parentFields;
    }

    protected function getSettingFields(){

        $settingFields = new FieldList('settings',trans('cmsable::forms.page-form.settings'));
        $settingFields->setSwitchable(TRUE);

        $settingFields->push($this->createPageTypeField());

        if($this->hasVisibilityField()){

            $options = [
                trans('cmsable::models.page.enums.visibility.show_in_menu'),
                trans('cmsable::models.page.enums.visibility.show_in_aside_menu'),
                trans('cmsable::models.page.enums.visibility.show_in_search'),
                trans('cmsable::models.page.enums.visibility.show_when_authorized')
            ];

            $settingFields->push(
                SelectFlagsField::create('visibility')
                                ->setTitle(trans('cmsable::models.page.fields.visibility'))
                                ->setSrc($options)
            );
        }
        else{
            $settingFields->push(
                CheckboxField::create('show_in_menu')->setTitle(trans('cmsable::models.page.fields.show_in_menu')),
                CheckboxField::create('show_in_aside_menu')->setTitle(trans('cmsable::models.page.fields.show_in_aside_menu')),
                CheckboxField::create('show_in_search')->setTitle(trans('cmsable::models.page.fields.show_in_search'))
            );
        }

        return $settingFields;

    }

    protected function hasVisibilityField(){
        $pageClass = Config::get('cmsable.page_model');
        return (strpos($pageClass,'Prodis') === false);
    }

    public function createActions(){
        $actions = parent::createActions();
        $actions('action_submit')->setTitle(trans('cmsable::forms.save'));
        $actions->push(Action::create('delete')->setTitle(trans('cmsable::forms.delete')));
        return $actions;
    }

    protected function createPageTypeField(){
        return SelectOneField::create('page_type')
                               ->setTitle(trans('cmsable::pagetypes.pagetype'))
                               ->setSrc(PageType::all($this->getRouteScope()),
                                        new Extractor('getId()', 'singularName()'));
    }
}