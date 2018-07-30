<?php namespace Cmsable\Html\Breadcrumbs;

use Illuminate\Contracts\Session\Session;
use Cmsable\Model\SiteTreeNodeInterface as Node;
use Cmsable\Html\Breadcrumbs\NodeCreatorInterface as NodeCreator;

class SessionStore implements StoreInterface
{

    public $sessionKey = 'cmsable_breadcrumbstore';

    /**
     * @var Session
     **/
    protected $session;

    /**
     * @var \Cmsable\Html\Breadcrumbs\NodeCreatorInterface
     **/
    protected $nodeCreator;

    /**
     * @var array
     **/
    protected $loadedCrumbIds = [];

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Cmsable\Html\Breadcrumbs\NodeCreatorInterface $nodeCreator
     **/
    public function __construct(Session $session, NodeCreator $nodeCreator)
    {
        $this->session = $session;
        $this->nodeCreator = $nodeCreator;
    }

    /**
     * {@inheritdoc}
     *
     * @param Cmsable\Html\Breadcrumbs\Crumbs $crumbs
     * @param string $routeName The current route name
     * @return void
     **/
    public function syncStoredCrumbs(Crumbs $crumbs, $routeName)
    {

        $this->purgeSessionCrumbs($crumbs);

        if($storable = $this->getStorableCrumbsByDepth($crumbs, $routeName)) {
            $this->session->put($this->sessionKey, $storable);
        }

    }

    /**
     * {@inheritdoc}
     *
     * @param string $routeName The route name (which is not the current)
     * @return array
     **/
    public function getStoredCrumbs($routeName)
    {

        if (!$storedArray = $this->getFromSessionForRoute($routeName)) {
            return [];
        }

        return $this->castToCrumbs($storedArray);

    }

    public function hasStoredCrumbs()
    {
        return $this->session->has($this->sessionKey);
    }

    protected function purgeSessionCrumbs($crumbs)
    {
        if (!$this->hasStoredCrumbs()) {
            return;
        }

        $depth = $this->getDepth($crumbs);

        $storedArray = $this->getFromSession();

        $purgedArray = $this->removeIndexesFrom($storedArray, $depth);

        if (!$purgedArray) {
            $this->session->forget($this->sessionKey);
            return;
        }

        $this->session->put($this->sessionKey, $purgedArray);
    }

    protected function getDepth($crumbs)
    {
        return count($crumbs);
    }

    protected function removeIndexesFrom($storedArray, $minIndex)
    {

        // I dont like writing into an array while iterating over it
        // so first collect the keys
        $removeKeys = [];

        foreach ($storedArray as $storedDepth=>$crumbArray) {
            if ($storedDepth >= $minIndex) {
                $removeKeys[] = $storedDepth;
            }
        }

        foreach ($removeKeys as $index) {
            unset($storedArray[$index]);
        }

        return $storedArray;

    }

    protected function castToCrumbs(array $storedArray)
    {

        $crumbs = [];

        foreach ($storedArray as $index=>$crumbArray) {

            $crumb = $this->fromArray($crumbArray);
            $crumbs[] = $crumb;
            $this->loadedCrumbIds[] = $crumb->getIdentifier();

        }

        return $crumbs;

    }

    protected function getFromSessionForRoute($routeName)
    {

        if (!$storedArray = $this->getFromSession()) {
            return [];
        }

        if (!$this->lastEntryMatchesRouteName($storedArray, $routeName)) {
            return [];
        }

        return $storedArray;

    }

    protected function getFromSession()
    {
        if (!$this->hasStoredCrumbs()) {
            return [];
        }

        if($storedArray = $this->session->get($this->sessionKey)) {
            return $storedArray;
        }

        return $storedArray;
    }

    protected function getStorableCrumbsByDepth(Crumbs $crumbs, $routeName)
    {

        $depth = 0;
        $storable = [];

        foreach ($crumbs as $crumb) {
            if($this->hasQueryParams($crumb) && !$this->wasLoadedFromStore($crumb)) {
                $storable[$depth] = $this->toArray($crumb, $routeName);
            }
            $depth++;

        }

        return $storable;

    }

    protected function lastEntryMatchesRouteName(array $storedCrumbs, $routeName)
    {
        $last = $this->lastEntry($storedCrumbs);
        return $last['routeName'] == $routeName;
    }

    protected function lastEntry(array $storedCrumbs)
    {
        $last = max(array_keys($storedCrumbs));
        return $storedCrumbs[$last];
    }

    protected function hasQueryParams(Node $crumb)
    {
        return (strpos($crumb->getPath(), '?') !== false);
    }

    protected function wasLoadedFromStore(Node $crumb)
    {
        return in_array($crumb->getIdentifier(), $this->loadedCrumbIds);
    }

    protected function toArray(Node $node, $routeName)
    {
        return [
            'id'        => $node->getIdentifier(),
            'path'      => $node->getPath(),
            'menu_title'=> $node->getMenuTitle(),
            'title'     => $node->getTitle(),
            'content'   => $node->getContent(),
            'routeName' => $routeName
        ];
    }

    /**
     * Deserializes a nodeArray to an node
     * TODO: parent_id and route_name are hacked without an interface
     *
     * @param array $nodeArray
     * @return \Cmsable\Model\SiteTreeNodeInterface
     **/
    protected function fromArray(array $nodeArray)
    {

        $node = $this->nodeCreator->makeNode();

        $node->id = $nodeArray['id'];
        $node->setPath($nodeArray['path']);
        $node->setMenuTitle($nodeArray['menu_title']);
        $node->setTitle($nodeArray['title']);
        $node->setContent($nodeArray['content']);
        $node->parent_id = 'parent-id-'.$nodeArray['id'];
        $node->route_name = $nodeArray['routeName'];

        return $node;

    }

}
