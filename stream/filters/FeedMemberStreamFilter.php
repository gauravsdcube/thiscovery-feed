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
use humhub\modules\thiscoveryFeed\services\ProfileStreamService;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Expression;
use yii\db\Query;

/**
 * Keeps the feed to posts from the member's spaces, plus profile posts the dashboard would show.
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

        $spaceIds = MemberSpaceService::contentContainerIds($this->user);
        $profileIds = ProfileStreamService::contentContainerIds($this->user);
        $containerIds = array_values(array_unique(array_merge($spaceIds, $profileIds)));
        if ($containerIds === []) {
            $this->query->andWhere('0=1');
            return;
        }

        $this->query->andWhere(['content.contentcontainer_id' => $containerIds]);
        if ($spaceIds !== []) {
            FeedShareService::hideDuplicateCopies($this->query, $spaceIds);
        }
        $this->query->andWhere(['or', ['content.pinned' => 0], ['content.pinned' => null]]);
        $this->query->andWhere(['or',
            ['content.visibility' => Content::VISIBILITY_PUBLIC],
            ['content.visibility' => null],
            ['and',
                ['content.visibility' => Content::VISIBILITY_PRIVATE],
                $this->privateContentCondition($spaceIds),
            ],
        ]);
    }

    /**
     * Private space posts stay visible to members. Private profile posts follow
     * the dashboard: the author, their own profile, and mutual friends.
     *
     * @param int[] $spaceIds
     * @return array<int|string, mixed>
     */
    private function privateContentCondition(array $spaceIds): array
    {
        $condition = ['or',
            ['content.created_by' => $this->user->id],
            ['content.contentcontainer_id' => (int) $this->user->contentcontainer_id],
        ];
        if ($spaceIds !== []) {
            $condition[] = ['content.contentcontainer_id' => $spaceIds];
        }
        if ($this->friendshipEnabled()) {
            $condition[] = ['and',
                ['exists', (new Query())
                    ->select(new Expression('1'))
                    ->from('user_friendship viewer_friend')
                    ->innerJoin('user profile_user', 'profile_user.id = viewer_friend.user_id')
                    ->where('profile_user.contentcontainer_id = content.contentcontainer_id')
                    ->andWhere(['viewer_friend.friend_user_id' => $this->user->id])],
                ['exists', (new Query())
                    ->select(new Expression('1'))
                    ->from('user_friendship author_friend')
                    ->innerJoin('user profile_owner', 'profile_owner.id = author_friend.friend_user_id')
                    ->where('profile_owner.contentcontainer_id = content.contentcontainer_id')
                    ->andWhere(['author_friend.user_id' => $this->user->id])],
            ];
        }

        return $condition;
    }

    private function friendshipEnabled(): bool
    {
        if (!Yii::$app->hasModule('friendship')) {
            return false;
        }

        $friendship = Yii::$app->getModule('friendship');

        return is_object($friendship) && method_exists($friendship, 'isFriendshipEnabled') && $friendship->isFriendshipEnabled();
    }
}
