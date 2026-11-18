<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

/**
 * Widget ids and the column each one belongs to.
 *
 * Labels live in the module so this class can be tested without Yii.
 */
final class WidgetCatalog
{
    public const SHORTCUTS = 'shortcuts';
    public const ANNOUNCEMENTS = 'announcements';
    public const EVENTS = 'events';
    public const FORMS = 'forms';

    /**
     * @return array<string, array{column: string}>
     */
    public static function definitions(): array
    {
        return [
            self::SHORTCUTS => ['column' => 'left'],
            self::ANNOUNCEMENTS => ['column' => 'right'],
            self::EVENTS => ['column' => 'right'],
            self::FORMS => ['column' => 'right'],
        ];
    }

    public static function exists(string $id): bool
    {
        return isset(self::definitions()[$id]);
    }

    public static function column(string $id): ?string
    {
        return self::definitions()[$id]['column'] ?? null;
    }
}
