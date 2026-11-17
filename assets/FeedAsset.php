<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\assets;

use yii\web\AssetBundle;

class FeedAsset extends AssetBundle
{
    public $sourcePath = '@thiscovery-feed/resources';

    public $css = [
        'css/feed.css',
    ];

    public $js = [
        'js/humhub.thiscoveryFeed.js',
    ];

    public $depends = [
        'humhub\assets\CoreApiAsset',
    ];
}
