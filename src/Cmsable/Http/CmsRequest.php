<?php


namespace Cmsable\Http;


use RuntimeException;
use Illuminate\Http\Request;


class CmsRequest extends Request implements CmsRequestInterface
{

    /**
     * @var CmsPath
     **/
    protected $cmsPath;

    /**
     * @var callable
     **/
    protected $cmsPathProvider;

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {

        if(!$this->cmsPath){
            $this->fillByProvider();
        }

        if($this->cmsPath){
            return $this->cmsPath->getRewrittenPath();
        }

        return $this->originalPath();

    }

    /**
     * Get the original (database-page) path
     *
     * @return string
     */
    public function originalPath()
    {
        return parent::path();
    }

    /**
     * Get the CmsPath object
     *
     * @return CmsPath
     **/
    public function getCmsPath()
    {
        if(!$this->cmsPath){
            $this->path();
        }
        return $this->cmsPath;
    }

    /**
     * Get the CmsPath object
     *
     * @return static
     **/
    public function setCmsPath(CmsPath $path)
    {
        $this->cmsPath = $path;
        return $this;
    }

    /**
     * Assign an object which will provide the CmsPath, the request itself
     * cant
     *
     * @param callable $provider
     * @return void
     **/
    public function provideCmsPath(callable $provider)
    {
        $this->cmsPathProvider = $provider;
    }

    /**
     * Set the cmsPath by the assigned provider. This way the whole loading
     * process is completely deferred.
     *
     * @return void
     **/
    protected function fillByProvider()
    {
        if (!is_callable($this->cmsPathProvider)) {
            throw new RuntimeException('No (callable) cmsPathProvider was set');
        }

        return call_user_func($this->cmsPathProvider, $this);
    }

}