<?php

namespace humhub\modules\auth\orcid;

use Yii;
use humhub\components\Event;
use humhub\modules\user\authclient\Collection;
use humhub\modules\auth\orcid\models\ConfigureForm;
use humhub\modules\auth\orcid\authclient\OrcidClient;

class Events
{
    /**
     * @param Event $event
     */
    public static function onAuthClientCollectionInit($event)
    {
        /** @var Collection $authClientCollection */
        $authClientCollection = $event->sender;

        if (!empty(ConfigureForm::getInstance()->enabled)) {
            $authClientCollection->setClient('orcid', [
                'class' => OrcidClient::class,
                'clientId' => ConfigureForm::getInstance()->clientId,
                'clientSecret' => ConfigureForm::getInstance()->clientSecret
            ]);
        }
    }
}