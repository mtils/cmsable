<?php namespace Cmsable\Routing\TreeScope;

use OutOfBoundsException;

use Illuminate\Http\Request;

class PathPrefixDetector implements DetectorInterface{

    /**
     * The scope repository
     *
     * @var RepositoryInterface
     **/
    protected $repository;

    public function __construct(RepositoryInterface $repository){
        $this->repository = $repository;
    }

    /**
     * Return the scope of a tree model for request $request
     *
     * @param Request $request
     * @return TreeScope
     **/
    public function detectScope(Request $request){

        $firstSegment = $this->getFirstSegment($request);

        try {
            return $this->repository->getByPathPrefix($firstSegment);
        } catch(OutOfBoundsException $e) {}

        try {
            return $this->repository->get(TreeScope::DEFAULT_NAME);
        } catch(OutOfBoundsException $e) {
            return new TreeScope;
        }

    }

    protected function getFirstSegment(Request $request){

        $segments = explode('/',trim($request->originalPath(),'/'));

        return $segments[0] ? $segments[0] : '/';

    }

}