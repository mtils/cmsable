<?php namespace Cmsable\Html\Breadcrumbs;

/**
 * This object stores and retrieves saved breadcrumbs. This is used to store
 * searches and retrieve em back from this store.
 * The BreadCrumbs\Factory uses this object internally. You should not use it
 * directly
 **/
interface StoreInterface
{

    /**
     * Synces the created $crumbs with the store. If new crumbs where created,
     * all stored with an depth >= have to be deleted.
     *
     * @param Cmsable\Html\Breadcrumbs\Crumbs $crumbs
     * @param string $routeName The current route name
     * @return void
     **/
    public function syncStoredCrumbs(Crumbs $crumbs, $routeName);

    /**
     * Returns saved crumbs for route named $routeName if found.
     *
     * @param string $routeName The route name (which is not the current)
     **/
    public function getStoredCrumbs($routeName);

}