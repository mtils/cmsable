<?php namespace Cmsable\Form;

use App;
use Form;
use Validator;
use FormObject\FieldList;
use FormObject\Field;
use FormObject\Field\TextField;
use FormObject\Field\HiddenField;
use FormObject\Field\Action;
use FormObject\Field\CheckboxField;
use FormObject\Field\BooleanRadioField;
use FormObject\Field\SelectOneField;
use Collection\Map\Extractor;
use CMS;

class PermissionablePageForm extends BasePageForm{

    public function getName(){
        return 'page-form';
    }

    public function createFields(){

        $parentFields = parent::createFields();

        $securityFields = new FieldList('security',trans('cmsable::forms.page-form.security'));
        $securityFields->setSwitchable(TRUE);

        $permFinder = App::make(__NAMESPACE__.'\PermissionFinder', array($this));

        $viewPermissions = $permFinder->buildFormValues($permFinder->getViewPermissions());

        $editPermissions = $permFinder->buildFormValues($permFinder->getEditPermissions());

        $deletePermissions = $permFinder->buildFormValues($permFinder->getDeletePermissions());

        $addChildPermissions = $permFinder->buildFormValues($permFinder->getAddChildPermissions());

        $securityFields->push(
            SelectOneField::create('view_permission')
                            ->setTitle(trans('cmsable::models.page.fields.view_permission'))
                            ->setSrc($viewPermissions),
            SelectOneField::create('edit_permission')
                            ->setTitle(trans('cmsable::models.page.fields.edit_permission'))
                            ->setSrc($editPermissions),
            SelectOneField::create('delete_permission')
                            ->setTitle(trans('cmsable::models.page.fields.delete_permission'))
                            ->setSrc($deletePermissions),
            SelectOneField::create('add_child_permission')
                            ->setTitle(trans('cmsable::models.page.fields.add_child_permission'))
                            ->setSrc($addChildPermissions)
        );
        $parentFields->push($securityFields);

        return $parentFields;
    }

    protected function createValidator(){

        $rules = array(
            'menu_title' => 'required|min:3|max:255',
            'url_segment' => 'required|min:1|max:255|url_segment|unique_segment_of:parent_id,id|no_manual_route:parent_id',
            'title' => 'required|min:3|max:255',
            'page_type' => 'required',
            'parent_id' => 'required'
        );

        return Validator::make($this->data, $rules);
    }

    protected function createPageTypeField(){
        return SelectOneField::create('page_type')
                               ->setTitle('Seitentyp')
                               ->setSrc(CMS::pageTypes()->all(),
                                        new Extractor('getId()', 'singularName()'));
    }
}