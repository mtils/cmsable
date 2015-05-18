<?php namespace Cmsable\Controller\SiteTree\Plugin;;

use Illuminate\Events\Dispatcher as LaravelDispatcher;
use Illuminate\Foundation\Application;

use FormObject\Form;

use Cmsable\PageType\RepositoryInterface as PageTypeRepository;
use Cmsable\Model\SiteTreeNodeInterface;

class Dispatcher{

    protected $pageTypeRepo;

    protected $events;

    protected $creator;

    protected static $pluginCache = [];

    public function __construct(PageTypeRepository $pageTypeRepo,
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

        if ($this->hasPluginForDispatcher($page->getPageTypeId())) {
            return;
        }

        $this->bootPlugin($page->getPageTypeId());

    }

    protected function bootPlugin($pageTypeId){

        if(!$plugin = $this->getFormPlugin($pageTypeId)){
            return;
        }

        $events = $this->events;

        $this->assureOneInstancePerDispatcher($plugin, $pageTypeId);

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

    protected function hasPluginForDispatcher($pageTypeId)
    {

        $dispatcherId = spl_object_hash($this->events);

        if (!isset(static::$pluginCache[$dispatcherId])) {
            return false;
        }

        if (!isset(static::$pluginCache[$dispatcherId][$pageTypeId])) {
            return false;
        }

        return true;
    }

    protected function assureOneInstancePerDispatcher(Plugin $plugin, $pageTypeId)
    {

        $dispatcherId = spl_object_hash($this->events);

        if (!isset(static::$pluginCache[$dispatcherId])) {
            static::$pluginCache[$dispatcherId] = [];
        }

        static::$pluginCache[$dispatcherId][$pageTypeId] = $plugin;

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