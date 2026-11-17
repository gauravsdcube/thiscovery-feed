<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\stream;

use humhub\modules\stream\models\WallStreamQuery;
use humhub\modules\thiscoveryFeed\stream\filters\FeedMemberStreamFilter;
use Yii;

class FeedStreamQuery extends WallStreamQuery
{
    public $pinnedContentSupport = false;

    protected function beforeApplyFilters()
    {
        $this->addFilterHandler(Yii::createObject([
            'class' => FeedMemberStreamFilter::class,
            'user' => $this->user,
        ]));

        parent::beforeApplyFilters();
    }
}
