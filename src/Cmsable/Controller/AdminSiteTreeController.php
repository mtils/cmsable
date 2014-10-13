<?php namespace Cmsable\Controller;

use Cmsable\Form\AdminPageForm;
use Cmsable\Model\SiteTreeModelInterface;
use AdminMenu;
use URL;
use HTML;

class AdminSiteTreeController extends SiteTreeController {

    /**
    * @brief hier wird bestimmt, welcher Loader benutzt wird
    * @var string
    */
    protected $cmsRouteName = 'admin';

    public function __construct(AdminPageForm $form, SiteTreeModelInterface $model){
        $this->form = $form;
        $this->model = $model;
        $this->registerViewMacros();
    }

    protected function registerViewMacros(){
        HTML::macro('jsTree', function($editedId){
            return AdminMenu::jsTree($editedId);
        });
    }

}
