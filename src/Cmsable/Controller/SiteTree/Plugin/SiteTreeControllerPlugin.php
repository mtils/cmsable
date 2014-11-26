<?php namespace Cmsable\Controller\SiteTree\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use FormObject\Field\TextField;
use FormObject\Field\SelectOneField;
use FormObject\Field\SelectOneGroup;
use FormObject\Validator\ValidatorInterface;

use Cmsable\Model\SiteTreeNodeInterface;

use Lang;
use Menu;
use App;

class SiteTreeControllerPlugin extends ConfigurablePlugin{

    public function modifyFormFields(FieldList $fields, SiteTreeNodeInterface $page){

        $fields('main')->offsetUnset('content');

        $fields('main')->push($this->getRootNodeSelect());

    }

    public function modifyFormValidator(ValidatorInterface $validator, SiteTreeNodeInterface $page){

        $validator->addRules([
            $this->fieldName('sitetree_root_id') => 'integer|min:1|max:999'
        ]);

    }

    protected function getRootNodeSelect(){

        $select = SelectOneField::create($this->fieldName('sitetree_root_id'),
                                         Lang::get('cmsable::forms.page-form.sitetree_root_id'));

        $scopeColumn = $this->getScopeColumn();
        $parentColumn = $this->getParentColumn();

        $rootNodes = $this->getPageModel()
                          ->whereNull($parentColumn)
                          ->orderBy($scopeColumn)
                          ->get();

        $entries = [];

        foreach($rootNodes as $root){
            $entries[$root->{$scopeColumn}] = $root->getMenuTitle();
        }

        $select->setSrc($entries);

        return $select;
    }

    protected function getPageModel(){
        return App::make('cmsable.tree-default')->makeNode();
    }

    protected function getScopeColumn(){
        return App::make('cmsable.tree-default')->rootCol();
    }

    protected function getParentColumn(){
        return App::make('cmsable.tree-default')->parentCol();
    }

}