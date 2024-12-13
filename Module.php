<?php

namespace humhub\modules\auth\orcid;

use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    public $resourcesPath = 'resources';

    public function getConfigUrl()
    {
        return Url::to(['/auth-orcid/admin']);
    }

    public function disable()
    {
        parent::disable();
    }
}