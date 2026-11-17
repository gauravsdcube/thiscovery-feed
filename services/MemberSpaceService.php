<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\db\Query;

/**
 * Spaces that contribute to a member's feed.
 *
 * A space is included when the person is a member, the space is enabled, and
 * the membership is still set to appear on the dashboard.
 */
final class MemberSpaceService
{
    /**
     * @return int[] content container ids
     */
    public static function contentContainerIds(User $user): array
    {
        $ids = (new Query())
            ->select('space.contentcontainer_id')
            ->from('space_membership')
            ->innerJoin('space', 'space.id = space_membership.space_id')
            ->where([
                'space_membership.user_id' => $user->id,
                'space_membership.status' => Membership::STATUS_MEMBER,
                'space_membership.show_at_dashboard' => 1,
                'space.status' => Space::STATUS_ENABLED,
            ])
            ->column();

        return array_values(array_map('intval', $ids));
    }
}
