<?php namespace Cmsable\PageType;

interface ConfigRepositoryInterface{

    public function makeConfig($pageType);

    public function getConfig($pageType, $pageId=null);

    public function saveConfig(ConfigInterface $config, $pageId=null);

    public function deleteConfig($configOrPageType, $pageId=null);

}