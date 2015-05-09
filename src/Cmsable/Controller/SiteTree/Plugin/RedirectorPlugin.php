<?php namespace Cmsable\Controller\SiteTree\Plugin;

use BeeTree\Helper;

use FormObject\Form;
use FormObject\FieldList;
use FormObject\Field\TextField;
use FormObject\Field\SelectOneField;
use FormObject\Field\SelectOneGroup;
use FormObject\Validator\ValidatorInterface;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\AdjacencyListSiteTreeModel;

use Lang;
use Menu;

class RedirectorPlugin extends Plugin{

    public function modifyFormFields(FieldList $fields, SiteTreeNodeInterface $page){

        $fields('main')->offsetUnset('content');

        $linkTypes = array(
            'internal' => Lang::get('cmsable::models.page.enums.redirect_type.internal'),
            'external' => Lang::get('cmsable::models.page.enums.redirect_type.external')
        );

        $selectGroup = SelectOneGroup::create(
            'redirect_type',
            Lang::get('cmsable::models.page.fields.redirect_type')
        )->setSrc($linkTypes);

        $selectGroup->push($this->getSiteTreeSelect($page));

        $selectGroup->push(
            TextField::create(
                'redirect__redirect_target_e',
                Lang::get('cmsable::forms.page-form.redirect_target_e')
            )
        );

        $fields('main')->push($selectGroup);

    }

    public function fillForm(Form $form, SiteTreeNodeInterface $model){
        switch($form('redirect_type')->getValue()){
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

    public function prepareSave(Form $form, SiteTreeNodeInterface $model){
        switch($form['redirect_type']){
            case 'internal':
                $model->redirect_target = $form['redirect__redirect_target_i'];
                break;
            case 'external':
                $model->redirect_target = $form['redirect__redirect_target_e'];
                break;
        }
    }

    public function modifyFormValidator(ValidatorInterface $validator, SiteTreeNodeInterface $page){

        $validator->addRules([
            'redirect_type' => 'in:internal,external',
            'redirect__redirect_target_e' => 'required_if:redirect_type,external|url'
        ]);

    }

    public function processPageTypeLeave(SiteTreeNodeInterface $page, $oldPageTypeId){
        $page->redirect_type = 'none';
        $page->redirect_target = 0;
    }

    protected function getSiteTreeSelect($page){
        $select = SelectOneField::create('redirect__redirect_target_i',Lang::get('cmsable::forms.page-form.redirect_target_i'));
        $select->setSrc($this->getPageList($page));
        return $select;
    }

    protected function getAllowedPageIDs($page){
        return array_keys($this->getPageList($page));
    }

    protected function getPageList($page){

        $list = array();
        $this->addFirstPageListEntry($list);

        $treeModel = $this->createTreeModel($page);

        foreach(Helper::flatify($treeModel->tree()) as $page){
            $list[$page->getIdentifier()] = $page->menu_title;
        }
        return $list;
    }

    protected function createTreeModel($page){
        return new AdjacencyListSiteTreeModel(get_class($page), $page->{$page->rootIdColumn});
    }

    protected function addFirstPageListEntry(&$list){
        $list['firstchild'] = Lang::get('cmsable::forms.page-form.redirect-to-first-child');
    }
}