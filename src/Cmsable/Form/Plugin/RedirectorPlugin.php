<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use FormObject\Field\TextField;
use FormObject\Field\SelectOneField;
use FormObject\Field\SelectOneGroup;
use Event;
use App;
use Lang;
use Menu;

class RedirectorPlugin extends Plugin{

    public function modifyFormFields(FieldList $fields){

        $fields('main')->offsetUnset('content');

        $linkTypes = array(
            'internal' => Lang::get('cmsable::models.page.enums.redirect_type.internal'),
            'external' => Lang::get('cmsable::models.page.enums.redirect_type.external')
        );

        $selectGroup = SelectOneGroup::create('redirect_type', Lang::get('cmsable::models.page.fields.redirect_type'))->setSrc($linkTypes);

        $selectGroup->push($this->getSiteTreeSelect());

        $selectGroup->push(TextField::create('redirect__redirect_target_e', Lang::get('cmsable::forms.page-form.redirect_target_e')));

        $fields('main')->push($selectGroup);

    }

    public function fillForm(Form $form, $model){
        switch($form['redirect_type']){
            case 'internal':
                $form('redirect__redirect_target_i')->setValue($model->redirect_target);
                $form('redirect__redirect_target_e')->setValue('');
                break;
            case 'external':
                $form('redirect__redirect_target_i')->setValue('firstchild');
                $form('redirect__redirect_target_e')->setValue($model->redirect_target);
                break;
        }
    }

    public function beforeSave(Form $form, $model){
        switch($form['redirect_type']){
            case 'internal':
                $model->redirect_target = $form['redirect__redirect_target_i'];
                break;
            case 'external':
                $model->redirect_target = $form['redirect__redirect_target_e'];
                break;
        }
    }

    public function modifyValidator($validator){

        $allowedPageIds = implode(',',$this->getAllowedPageIDs());

        $rules = array(
            'redirect_type' => 'in:internal,external',
            'redirect__redirect_target_e' => 'required_if:redirect_type,external|url',
            'redirect__redirect_target_i' => "required_if:redirect_type,internal|in:$allowedPageIds",
        );
        $validator->setRules(array_merge($validator->getRules(),$rules));
    }

    protected function getSiteTreeSelect(){
        $select = SelectOneField::create('redirect__redirect_target_i',Lang::get('cmsable::forms.page-form.redirect_target_i'));
        $select->setSrc($this->getPageList());
        return $select;
    }

    protected function getAllowedPageIDs(){
        return array_keys($this->getPageList());
    }

    protected function getPageList(){

        $list = array();
        $this->addFirstPageListEntry($list);

        foreach(Menu::flat() as $page){
            $list[$page->getIdentifier()] = $page->menu_title;
        }
        return $list;
    }

    protected function addFirstPageListEntry(&$list){
        $list['firstchild'] = Lang::get('cmsable::forms.page-form.redirect-to-first-child');
    }
}