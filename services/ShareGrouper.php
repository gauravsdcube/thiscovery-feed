<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

/**
 * Groups copies of one post that were published to several spaces together.
 */
final class ShareGrouper
{
    /**
     * @param list<array{id: int|string, created_by: int|string|null, created_at: string, message: string, container_id: int|string}> $rows
     * @return list<list<int>>
     */
    public static function groups(array $rows, int $gapSeconds = 120): array
    {
        $groups = [];
        $current = [];
        $startedAt = null;

        foreach ($rows as $row) {
            $at = strtotime((string) $row['created_at']) ?: 0;
            if ($current !== [] && !self::continues($current, $row, $startedAt, $at, $gapSeconds)) {
                self::keep($groups, $current);
                $current = [];
                $startedAt = null;
            }
            if ($current === []) {
                $startedAt = $at;
            }
            $current[] = $row;
        }

        self::keep($groups, $current);

        return $groups;
    }

    /**
     * @param list<array{id: int|string, created_by: int|string|null, created_at: string, message: string, container_id: int|string}> $current
     * @param array{id: int|string, created_by: int|string|null, created_at: string, message: string, container_id: int|string} $row
     */
    private static function continues(array $current, array $row, ?int $startedAt, int $at, int $gapSeconds): bool
    {
        $first = $current[0];
        if ((string) $row['created_by'] !== (string) $first['created_by']) {
            return false;
        }
        if ((string) $row['message'] !== (string) $first['message']) {
            return false;
        }
        if ($startedAt === null || $at < $startedAt || ($at - $startedAt) > $gapSeconds) {
            return false;
        }
        foreach ($current as $item) {
            if ((string) $item['container_id'] === (string) $row['container_id']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<list<int>> $groups
     * @param list<array{id: int|string, container_id: int|string}> $current
     */
    private static function keep(array &$groups, array $current): void
    {
        $containers = [];
        foreach ($current as $row) {
            $containers[(string) $row['container_id']] = true;
        }
        if (count($current) < 2 || count($containers) < 2) {
            return;
        }

        $groups[] = array_map(static fn(array $row): int => (int) $row['id'], $current);
    }
}
