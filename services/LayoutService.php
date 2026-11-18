<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

/**
 * Resolves which widgets are visible, and in what order.
 *
 * An administrator layout is the catalogue a member is allowed to use.
 * A saved member layout is a subset of that catalogue, in the member's order.
 */
final class LayoutService
{
    /**
     * @return array{left: string[], right: string[]}
     */
    public static function defaultLayout(): array
    {
        return [
            'left' => [WidgetCatalog::SHORTCUTS],
            'right' => [WidgetCatalog::ANNOUNCEMENTS],
        ];
    }

    /**
     * @param array<string, mixed> $layout
     * @return array{left: string[], right: string[]}
     */
    public static function normalize(array $layout): array
    {
        $result = ['left' => [], 'right' => []];

        foreach (['left', 'right'] as $column) {
            $ids = $layout[$column] ?? [];
            if (!is_array($ids)) {
                continue;
            }

            foreach ($ids as $id) {
                if (!is_string($id) || !WidgetCatalog::exists($id)) {
                    continue;
                }
                if (in_array($id, $result['left'], true) || in_array($id, $result['right'], true)) {
                    continue;
                }
                $result[$column][] = $id;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $adminLayout
     * @param array<string, mixed>|null $userLayout null when the member has not customized
     * @return array{left: string[], right: string[]}
     */
    public static function resolve(array $adminLayout, ?array $userLayout): array
    {
        $admin = self::normalize($adminLayout);
        if ($userLayout === null) {
            return $admin;
        }

        $user = self::normalize($userLayout);
        foreach (['left', 'right'] as $column) {
            $user[$column] = array_values(array_filter(
                $user[$column],
                static fn(string $id): bool => in_array($id, $admin[$column], true),
            ));
        }

        return $user;
    }

    /**
     * @param string[] $selectedIds
     * @param array<string, mixed> $order sort numbers keyed by widget id
     * @param array<string, mixed> $columns left or right, keyed by widget id
     * @return array{left: string[], right: string[]}
     */
    public static function fromSelection(array $selectedIds, array $order, array $columns = []): array
    {
        $items = [];
        foreach ($selectedIds as $id) {
            if (!is_string($id) || !WidgetCatalog::exists($id)) {
                continue;
            }
            $column = $columns[$id] ?? WidgetCatalog::column($id);
            if (!in_array($column, ['left', 'right'], true)) {
                $column = WidgetCatalog::column($id);
            }
            $items[] = [
                'id' => $id,
                'column' => $column,
                'sort' => isset($order[$id]) && is_numeric($order[$id]) ? (int) $order[$id] : 100,
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return $a['sort'] <=> $b['sort'] ?: strcmp($a['id'], $b['id']);
        });

        $columns = ['left' => [], 'right' => []];
        foreach ($items as $item) {
            $columns[$item['column']][] = $item['id'];
        }

        return self::normalize($columns);
    }

    /**
     * @param array{left?: string[], right?: string[]} $layout
     * @return array<string, string>
     */
    public static function columnMap(array $layout): array
    {
        $map = [];
        foreach (['left', 'right'] as $column) {
            foreach ($layout[$column] ?? [] as $id) {
                if (is_string($id) && !isset($map[$id])) {
                    $map[$id] = $column;
                }
            }
        }

        return $map;
    }
}
