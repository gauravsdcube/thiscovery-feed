<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\dashboard\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Query;

/**
 * Profile posts the feed shows, using the same rule as the dashboard.
 *
 * A member always sees their own profile. Other profiles are included when
 * the dashboard would include them: every profile, or only followed profiles.
 */
final class ProfileStreamService
{
    /**
     * @return int[]
     */
    public static function contentContainerIds(User $user): array
    {
        if (self::includesEveryProfile($user)) {
            $ids = (new Query())
                ->select('contentcontainer_id')
                ->from('user')
                ->where(['status' => User::STATUS_ENABLED])
                ->column();

            return array_values(array_unique(array_map('intval', $ids)));
        }

        $ids = [];
        if ($user->contentcontainer_id) {
            $ids[] = (int) $user->contentcontainer_id;
        }

        $followed = (new Query())
            ->select('user.contentcontainer_id')
            ->from('user_follow')
            ->innerJoin('user', 'user.id = user_follow.object_id')
            ->where([
                'user_follow.user_id' => $user->id,
                'user_follow.object_model' => User::class,
                'user.status' => User::STATUS_ENABLED,
            ])
            ->column();

        foreach ($followed as $id) {
            $ids[] = (int) $id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Guids used for the new-post banner. This stays limited to the member and
     * the profiles they follow, even when the dashboard shows every profile.
     *
     * @return string[]
     */
    public static function guids(User $user): array
    {
        $guids = [];
        if (is_string($user->guid) && $user->guid !== '') {
            $guids[] = $user->guid;
        }

        $followed = (new Query())
            ->select('user.guid')
            ->from('user_follow')
            ->innerJoin('user', 'user.id = user_follow.object_id')
            ->where([
                'user_follow.user_id' => $user->id,
                'user_follow.object_model' => User::class,
                'user.status' => User::STATUS_ENABLED,
            ])
            ->column();

        foreach ($followed as $guid) {
            if (is_string($guid) && $guid !== '') {
                $guids[] = $guid;
            }
        }

        return array_values(array_unique($guids));
    }

    private static function includesEveryProfile(User $user): bool
    {
        $dashboard = Yii::$app->getModule('dashboard');
        if (!$dashboard instanceof Module) {
            return false;
        }

        return $dashboard->autoIncludeProfilePosts === Module::STREAM_AUTO_INCLUDE_PROFILE_POSTS_ALWAYS
            || ($dashboard->autoIncludeProfilePosts === Module::STREAM_AUTO_INCLUDE_PROFILE_POSTS_ADMIN_ONLY && $user->isSystemAdmin());
    }
}
