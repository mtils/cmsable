<?php namespace Cmsable\Form;

use App;
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
                            ->setSrc($editPermissions)
        );
        $parentFields->push($securityFields);

        return $parentFields;
    }

}