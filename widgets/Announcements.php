<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\widgets;

use humhub\components\Widget;
use humhub\modules\thiscoveryFeed\Module;
use humhub\modules\thiscoveryFeed\services\AnnouncementService;
use humhub\modules\user\models\User;
use Yii;

class Announcements extends Widget
{
    public ?User $user = null;

    public function run()
    {
        if (!$this->user instanceof User) {
            return '';
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-feed');

        return $this->render('announcements', [
            'items' => AnnouncementService::forUser($this->user, $module->announcementLimit()),
            'lines' => $module->announcementLines(),
        ]);
    }
}
