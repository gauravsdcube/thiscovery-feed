<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\post\permissions\CreatePost;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * Spaces the current member is allowed to publish a post into.
 */
final class PostableSpaceService
{
    /**
     * @return Space[]
     */
    public static function forUser(User $user): array
    {
        if (!Yii::$app->hasModule('post')) {
            return [];
        }

        $spaces = [];
        foreach (Membership::findByUser($user)->all() as $membership) {
            $space = $membership->space;
            if (!$space instanceof Space || $space->isArchived()) {
                continue;
            }
            if (!$space->moduleManager->isEnabled('post')) {
                continue;
            }
            if (!$space->can(CreatePost::class)) {
                continue;
            }
            $spaces[] = $space;
        }

        return $spaces;
    }

    /**
     * @param Space[] $spaces
     */
    public static function find(array $spaces, string $guid): ?Space
    {
        foreach ($spaces as $space) {
            if ($space->guid === $guid) {
                return $space;
            }
        }

        return null;
    }
}
