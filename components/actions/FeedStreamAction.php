<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\components\actions;

use humhub\modules\content\widgets\stream\StreamEntryOptions;
use humhub\modules\stream\actions\Stream;
use humhub\modules\thiscoveryFeed\stream\FeedStreamQuery;

class FeedStreamAction extends Stream
{
    public $streamQueryClass = FeedStreamQuery::class;

    protected function initStreamEntryOptions()
    {
        return parent::initStreamEntryOptions()->viewContext(StreamEntryOptions::VIEW_CONTEXT_DASHBOARD);
    }
}
