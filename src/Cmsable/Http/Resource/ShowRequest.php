<?php namespace Cmsable\Http\Resource;

use Illuminate\Http\Request;
use Cmsable\Http\Contracts\DecoratesRequest;
use Cmsable\Http\ReplicateRequest;
use Cmsable\Resource\Contracts\ReceivesResourceMapper;

class ShowRequest extends Request implements DecoratesRequest, ReceivesResourceMapper
{
    use ReplicateRequest;
    use FindsModelByKey;

    protected function getModelFromStore($key)
    {

        $model = $this->modelFinder()->find($key);

        if ($model) {
            $this->fireAction('show', $model);
        }

        return $model;
    }

}