<?php

use humhub\modules\auth\orcid\Events;
use humhub\modules\auth\orcid\Module;
use humhub\modules\user\authclient\Collection;

return [
    'id' => 'auth-orcid',
    'class' => Module::class,
    'namespace' => 'humhub\modules\auth\orcid',
    'events' => [
        [Collection::class, Collection::EVENT_AFTER_CLIENTS_SET, [Events::class, 'onAuthClientCollectionInit']]
    ],
];