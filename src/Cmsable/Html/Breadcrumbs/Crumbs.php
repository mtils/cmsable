<?php namespace Cmsable\Html\Breadcrumbs;

use InvalidArgumentException;

use Collection\OrderedList;
use Cmsable\Model\SiteTreeNodeInterface;


class Crumbs extends OrderedList{

    protected $nodeCreator;

    protected $autoIncrementor = 0;

    public function __construct(NodeCreatorInterface $creator){
        $this->nodeCreator = $creator;
    }


    public function make($menuTitle=NULL, $path=NULL, $title=NULL, $content=NULL){

        $crumb = $this->nodeCreator->newNode();

        if($menuTitle){
            $crumb->setMenuTitle($menuTitle);
        }

        if($path){
            $crumb->setPath($path);
        }

        if($title){
            $crumb->setTitle($title);
        }

        if($content){
            $crumb->setContent($content);
        }

        // Produce some custom Ids to prevent false parent/child relations
        $crumb->id = 'custom-id-'.$this->autoIncrementor;
        $crumb->parent_id = 'parent-'.$crumb->id;
        $this->autoIncrementor++;

        return $crumb;

    }

    public function add($menuTitleOrCrumb=NULL, $path=NULL, $title=NULL, $content=NULL){

        if($menuTitleOrCrumb instanceof SiteTreeNodeInterface){
            $this->append($menuTitleOrCrumb);
            return $menuTitleOrCrumb;
        }

        $crumb = $this->make($menuTitleOrCrumb, $path, $title, $content);

        $this->append($crumb);

        return $crumb;
    }

    public function current(){
        return $this->last();
    }

    public function append($value): static
    {

        if(!$value instanceof SiteTreeNodeInterface){
            throw new InvalidArgumentException('You can only append SiteTreeNodeInterface. Use add instead');
        }

        if($node = $this->current()){

            if($value->getParentIdentifier() != $node->getIdentifier())
            {
                 $node->addChildNode($value);
                 $value->setParentNode($node);
            }
        }

        return parent::append($value);
    }

}