<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class ManageFeed extends BaseAdminPermission
{
    protected $id = 'thiscovery_feed_manage';
    protected $moduleId = 'thiscovery-feed';

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('ThiscoveryFeedModule.base', 'Manage the feed');
        $this->description = Yii::t(
            'ThiscoveryFeedModule.base',
            'Choose whether the feed replaces the Dashboard, and which widgets members see by default.'
        );
    }
}
