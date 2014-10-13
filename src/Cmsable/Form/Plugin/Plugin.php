<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use Event;
use Cmsable\Cms\PageType;

class Plugin implements PluginInterface{

    /**
     * @brief The PageType of the matching Page
     * @var Cmsable\Cms\PageType
     **/
    protected $pageType;

    /**
     * @brief Returns the PageType of its Page instance. The pagetype is
     *        immediatly after instanciation. If your plugin does anything depending on its
     *        PageType, you can ask by $this->getPageType()
     *
     * @return Cmsable\Cms\PageType
     **/
    public function getPageType(){
        return $this->pageType;
    }

    /**
     * @brief This method is immediatly called after instanciation
     *        of this Plugin.
     *
     * @see Cmsable\Cms\PageType::getFormPlugin
     * @return Cmsable\Cms\PageType
     **/
    public function setPageType(PageType $type){
        $this->pageType = $type;
        return $this;
    }

    /**
     * @brief The base method of PluginInterface is final, because it devides into
     *        fields, validator and actions
     *
     * @param FormObject\Form $form The page form
     * @return void
     **/
    public final function modifyForm(Form $form){

        $formName = $form->getName();
        $mod = $this;

        Event::listen("form.fields-created.$formName", function($fields) use ($mod){
            $mod->modifyFormFields($fields);
        });

        Event::listen("form.validator-created.$formName", function($validator) use ($mod){
            $mod->modifyValidator($validator);
        });

        Event::listen("form.form.actions-created.$formName", function($validator) use ($mod){
            $mod->modifyValidator($validator);
        });
    }

    /**
     * @brief This method is called after the form created its fields. 
     *        The sequence is:
     *        1. Form::createFields()
     *        2. Form sends Event form.fields-created.$formname
     *        3. $this->modifyFormFields(FieldList $mainFieldList) is called
     *        This method is called before it is filled. So here is no chance to do
     *        something depending on the page or request
     *
     * @param FormObject\FieldList
     * @return void
     **/
    public function modifyFormFields(FieldList $fields){}

    /**
     * @brief This method is called after the form created its validator
     *
     * @see self::modifyFormFields
     * @param mixed $validator
     * @return void
     **/
    public function modifyValidator($validator){}

    /**
     * @brief This method is called after the form created its actions
     *
     * @see self::modifyFormFields
     * @param FormObject\FieldList $fields
     * @return void
     **/
    public function modifyActions(FieldList $fields){}

    /**
     * @brief This method is called before the form is filled. If you need to fill
     *        selects or add fields depending on its page this is the right place
     *
     * @param FormObject $form The page form
     * @param SiteTreeNodeInterface $model
     * @return void
     **/
    public function beforeFillForm(Form $form, $model){}

    /**
     * @brief This method is called after the form is filled by SiteTreeController.
     *
     * @param FormObject $form The page form
     * @param SiteTreeNodeInterface $model
     * @return void
     **/
    public function fillForm(Form $form, $model){}

    public function beforeSave(Form $form, $model){}

    public function afterSave(Form $form, $model){}

}