<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\models;

use yii\db\ActiveRecord;

/**
 * Links the copies of one post that was published to more than one space.
 *
 * @property int $content_id
 * @property string $share_id
 */
class FeedShare extends ActiveRecord
{
    public static function tableName()
    {
        return 'thiscovery_feed_share';
    }
}
