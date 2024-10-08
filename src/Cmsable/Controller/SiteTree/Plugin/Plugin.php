<?php namespace Cmsable\Controller\SiteTree\Plugin;

use FormObject\Form;
use FormObject\Factory as FormFactory;
use FormObject\FieldList;
use FormObject\Validator\ValidatorInterface;

use Cmsable\PageType\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

abstract class Plugin{

    /**
     * @brief The PageType of the matching Page
     * @var \Cmsable\PageType\PageType
     **/
    protected $pageType;

    /**
     * @var string
     **/
    protected $name;

    /**
     * @var \FormObject\Factory
     **/
    protected $formFactory;

    /**
     * @brief Returns the PageType of its Page instance. The pagetype is
     *        immediatly after instanciation. If your plugin does anything depending on its
     *        PageType, you can ask by $this->getPageType()
     *
     * @return \Cmsable\PageType\PageType
     **/
    public function getPageType(){
        return $this->pageType;
    }

    /**
     * @brief This method is immediatly called after instanciation
     *        of this Plugin.
     *
     * @see \Cmsable\PageType\PageType::getFormPlugin
     * @return \Cmsable\PageType\PageType
     **/
    public function setPageType(PageType $type){
        $this->pageType = $type;
        return $this;
    }

    /**
     * @brief This method is called after the form created its fields.
     *        The sequence is:
     *        1. Form::createFields()
     *        2. Form sends Event form.fields-setted.$formname
     *        3. $this->modifyFormFields(FieldList $mainFieldList) is called
     *        This method is called before it is filled. So here is no chance to do
     *        something depending on the page or request
     *
     * @param FormObject\FieldList
     * @return void
     **/
    public function modifyFormFields(FieldList $fields, SiteTreeNodeInterface $page){}

    /**
     * @brief This method is called after the form created its validator
     *
     * @see self::modifyFormFields
     * @param mixed $validator
     * @return void
     **/
    public function modifyFormValidator(ValidatorInterface $validator, SiteTreeNodeInterface $page){}

    /**
     * @brief This method is called after the form created its actions
     *
     * @see self::modifyFormFields
     * @param FormObject\FieldList $fields
     * @return void
     **/
    public function modifyFormActions(FieldList $actions, SiteTreeNodeInterface $page){}

    /**
     * @brief This method is called after the form is filled by SiteTreeController.
     *
     * @param FormObject $form The page form
     * @param SiteTreeNodeInterface $model
     * @return void
     **/
    public function fillForm(Form $form, SiteTreeNodeInterface $page){}

    public function prepareSave(Form $form, SiteTreeNodeInterface $page){}

    public function finalizeSave(Form $form, SiteTreeNodeInterface $page){}

    public function processPageTypeLeave(SiteTreeNodeInterface $page, $oldPageTypeId){}

    public function getName()
    {
        if (!$this->name) {
            $this->name = $this->calculatePluginName();
        }
        return $this->name;
    }

    /**
     * Forward any calls to the form factory to allow easy field creation
     * via $this->textField() or $this->selectOneField()
     *
     * @param $method
     * @param array $params (optional)
     * @return \FormObject\Field
     **/
    public function __call($fieldName, array $params=[])
    {
        return $this->formFactory->__call($fieldName, $params);
    }

    public function getFormFactory()
    {
        return $this->formFactory;
    }

    public function setFormFactory(FormFactory $factory)
    {
        $this->formFactory = $factory;
        return $this;
    }

    protected function calculatePluginName()
    {
        $name = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '-$1', class_basename(get_called_class())));

        if (!str_ends_with($name, '-plugin')){
            return $name;
        }

        return substr($name,0, strlen($name)-7);
    }

}