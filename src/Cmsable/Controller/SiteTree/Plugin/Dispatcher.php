<?php namespace Cmsable\Controller\SiteTree\Plugin;;

use Illuminate\Events\Dispatcher as LaravelDispatcher;
use Illuminate\Foundation\Application;

use FormObject\Form;

use Cmsable\Cms\PageTypeRepositoryInterface;
use Cmsable\Model\SiteTreeNodeInterface;

class Dispatcher{

    protected $pageTypeRepo;

    protected $events;

    protected $creator;

    public function __construct(PageTypeRepositoryInterface $pageTypeRepo,
                                LaravelDispatcher $events,
                                Application $creator){

        $this->pageTypeRepo = $pageTypeRepo;

        $this->events = $events;

        $this->creator = $creator;

        $this->addGlobalSubscriptions($this->events);

        $this->addPageTypeLeaveSubscription($this->events);

    }

    protected function addGlobalSubscriptions(LaravelDispatcher $dispatcher){

        $dispatcher->listen("sitetree.edit", $this);

        $dispatcher->listen("sitetree.update", $this);

    }

    protected function addPageTypeLeaveSubscription(LaravelDispatcher $dispatcher){

        $dispatcher->listen('sitetree.page-type-leaving', function($page, $oldPageTypeId){

            if(!$plugin = $this->getFormPlugin($oldPageTypeId)){
                return;
            }

            $plugin->processPageTypeLeave($page, $oldPageTypeId);

        });

    }

    public function __invoke(SiteTreeNodeInterface $page){

        $this->bootPlugin($page->getPageTypeId());

    }

    protected function bootPlugin($pageTypeId){

        if(!$plugin = $this->getFormPlugin($pageTypeId)){
            return;
        }

        $events = $this->events;

        $this->events->listen(

            "sitetree.$pageTypeId.form-created",

            function(Form $form, SiteTreeNodeInterface $page) use ($plugin, $events){

                $formName = $form->getName();

                $events->listen("form.fields-setted.$formName", function($fields) use ($page, $plugin){
                    $plugin->modifyFormFields($fields, $page);
                });

                $events->listen("form.validator-setted.$formName", function($validator) use ($page, $plugin){
                    $plugin->modifyFormValidator($validator, $page);
                });

                $events->listen("form.actions-setted.$formName", function($actions) use ($page, $plugin){
                    $plugin->modifyFormActions($actions, $page);
                });

            }
        );

        $this->events->listen(

            "sitetree.$pageTypeId.form-filled",

            function(Form $form, SiteTreeNodeInterface $page) use ($plugin){

                $plugin->fillForm($form, $page);

            }
        );

        $this->events->listen(

            "sitetree.$pageTypeId.updating",

            function(Form $form, SiteTreeNodeInterface $page) use ($plugin){

                $plugin->prepareSave($form, $page);

            }
        );

        $this->events->listen(

            "sitetree.$pageTypeId.updated",

            function(Form $form, SiteTreeNodeInterface $page) use ($plugin){

                $plugin->finalizeSave($form, $page);

            }
        );


    }

    protected function getFormPlugin($pageTypeId){

        if($pageType = $this->pageTypeRepo->get($pageTypeId)){

            if($pluginClass = $pageType->getFormPluginClass()){

                $plugin = $this->creator->make($pluginClass);
                $plugin->setPageType($pageType);
                return $plugin;

            }

        }

    }

}