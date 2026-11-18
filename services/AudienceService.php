<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

/**
 * Decides which spaces receive a feed post.
 *
 * "Everyone" is not a list of spaces. That choice creates one profile post,
 * the same way the dashboard composer does. "Spaces" is the subset they
 * picked, still limited to spaces they can post in.
 */
final class AudienceService
{
    public const EVERYONE = 'everyone';

    public const SPACES = 'spaces';

    /**
     * @param string[] $allowedGuids spaces the member can post in, in display order
     * @param string[] $selectedGuids
     * @return string[]
     */
    public static function resolve(string $audience, array $allowedGuids, array $selectedGuids): array
    {
        $allowed = [];
        foreach ($allowedGuids as $guid) {
            if (is_string($guid) && $guid !== '' && !in_array($guid, $allowed, true)) {
                $allowed[] = $guid;
            }
        }

        if ($audience === self::EVERYONE) {
            return [];
        }

        $selected = [];
        foreach ($selectedGuids as $guid) {
            if (is_string($guid) && $guid !== '') {
                $selected[$guid] = true;
            }
        }

        return array_values(array_filter(
            $allowed,
            static fn(string $guid): bool => isset($selected[$guid]),
        ));
    }
}
