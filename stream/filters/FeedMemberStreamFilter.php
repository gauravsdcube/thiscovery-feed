<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\stream\filters;

use humhub\modules\content\models\Content;
use humhub\modules\stream\models\filters\StreamQueryFilter;
use humhub\modules\thiscoveryFeed\services\FeedShareService;
use humhub\modules\thiscoveryFeed\services\MemberSpaceService;
use humhub\modules\user\models\User;

/**
 * Keeps the feed to published content from spaces the member belongs to.
 * Pinned entries are left out of the centre stream; they are shown in the announcements rail.
 */
class FeedMemberStreamFilter extends StreamQueryFilter
{
    public ?User $user = null;

    public function apply()
    {
        if (!$this->user instanceof User) {
            $this->query->andWhere('0=1');
            return;
        }

        $containerIds = MemberSpaceService::contentContainerIds($this->user);
        if ($containerIds === []) {
            $this->query->andWhere('0=1');
            return;
        }

        $this->query->andWhere(['content.contentcontainer_id' => $containerIds]);
        FeedShareService::hideDuplicateCopies($this->query, $containerIds);
        $this->query->andWhere(['or', ['content.pinned' => 0], ['content.pinned' => null]]);
        $this->query->andWhere(['or',
            ['content.visibility' => Content::VISIBILITY_PUBLIC],
            ['content.visibility' => null],
            ['content.visibility' => Content::VISIBILITY_PRIVATE],
        ]);
    }
}
