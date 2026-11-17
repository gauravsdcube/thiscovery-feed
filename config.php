<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\components\Application;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryFeed\Events;
use humhub\modules\thiscoveryFeed\Module;
use humhub\widgets\TopMenu;
use yii\base\View;

return [
    'id' => 'thiscovery-feed',
    'class' => Module::class,
    'namespace' => 'humhub\modules\thiscoveryFeed',
    'events' => [
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => [Events::class, 'onTopMenuInit']],
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onApplicationBeforeAction']],
        ['class' => View::class, 'event' => View::EVENT_AFTER_RENDER, 'callback' => [Events::class, 'onViewAfterRender']],
    ],
    'urlManagerRules' => [
        'feed' => 'thiscovery-feed/feed/index',
    ],
];
