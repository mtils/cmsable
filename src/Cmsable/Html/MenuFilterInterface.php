<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;

interface MenuFilterInterface{
    public function isVisible(SiteTreeNodeInterface $page);
}