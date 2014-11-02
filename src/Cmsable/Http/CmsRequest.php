<?php namespace Cmsable\Http;

use InvalidArgumentException;

use Illuminate\Http\Request;

use Cmsable\Support\EventSenderTrait;

class CmsRequest extends Request implements CmsRequestInterface{

    use EventSenderTrait;

    public static $pathRequestedEventName = 'cmsable::request.path-requested';

    protected $cmsPath;

    protected $cmsApplication;

    public function path()
    {

        if(!$this->cmsPath){
            $this->fireEvent(self::$pathRequestedEventName,[$this],$once=TRUE);
        }

        if($this->cmsPath){
            return $this->cmsPath->getRewrittenPath();
        }
        return $this->originalPath();

    }

    public function originalPath()
    {
        return parent::path();
    }

    public function getCmsPath()
    {
        return $this->cmsPath;
    }

    public function setCmsPath(CmsPath $path)
    {
        $this->cmsPath = $path;
        return $this;
    }

}