<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\space\models\Space;
use Yii;
use yii\db\Query;

/**
 * Groups spaces the way Classified Spaces does, when that module is enabled.
 *
 * Categories follow classified_space_category.sort_order. Spaces inside a
 * category follow space.sort_order. Both fall back to the name.
 */
final class SpaceCategoryService
{
    public static function active(): bool
    {
        return Yii::$app->hasModule('classified-space');
    }

    /**
     * @param Space[] $spaces
     * @return array<int, array{name: string, spaces: Space[], children: array<int, array{name: string, spaces: Space[]}>}>|null
     */
    public static function tree(array $spaces): ?array
    {
        if ($spaces === [] || !self::active()) {
            return null;
        }

        $byId = [];
        foreach ($spaces as $space) {
            $byId[(int) $space->id] = $space;
        }

        $rows = (new Query())
            ->select([
                'space_id' => 'classified_space.space_id',
                'category_id' => 'category.id',
                'category_name' => 'category.name',
                'category_sort' => 'category.sort_order',
                'parent_id' => 'category.parent_category_id',
                'parent_name' => 'parent.name',
                'parent_sort' => 'parent.sort_order',
            ])
            ->from('classified_space')
            ->innerJoin('classified_space_category category', 'category.id = classified_space.category_id')
            ->leftJoin('classified_space_category parent', 'parent.id = category.parent_category_id')
            ->where(['classified_space.space_id' => array_keys($byId)])
            ->all();

        if ($rows === []) {
            return null;
        }

        $assignment = [];
        foreach ($rows as $row) {
            $spaceId = (int) $row['space_id'];
            if (!isset($assignment[$spaceId]) || self::rowRank($row) < self::rowRank($assignment[$spaceId])) {
                $assignment[$spaceId] = $row;
            }
        }

        $parents = [];
        foreach ($assignment as $spaceId => $row) {
            $space = $byId[$spaceId];
            $hasParent = $row['parent_id'] !== null && $row['parent_id'] !== '' && $row['parent_name'] !== null;
            $parentId = $hasParent ? (int) $row['parent_id'] : (int) $row['category_id'];
            if (!isset($parents[$parentId])) {
                $parents[$parentId] = [
                    'name' => $hasParent ? (string) $row['parent_name'] : (string) $row['category_name'],
                    'sort' => $hasParent ? (int) $row['parent_sort'] : (int) $row['category_sort'],
                    'spaces' => [],
                    'children' => [],
                ];
            }
            if ($hasParent) {
                $childId = (int) $row['category_id'];
                if (!isset($parents[$parentId]['children'][$childId])) {
                    $parents[$parentId]['children'][$childId] = [
                        'name' => (string) $row['category_name'],
                        'sort' => (int) $row['category_sort'],
                        'spaces' => [],
                    ];
                }
                $parents[$parentId]['children'][$childId]['spaces'][] = $space;
            } else {
                $parents[$parentId]['spaces'][] = $space;
            }
        }

        uasort($parents, [self::class, 'bySortThenName']);
        $tree = [];
        foreach ($parents as $parent) {
            self::sortSpaces($parent['spaces']);
            $children = $parent['children'];
            uasort($children, [self::class, 'bySortThenName']);
            $parent['children'] = [];
            foreach ($children as $child) {
                self::sortSpaces($child['spaces']);
                $parent['children'][] = [
                    'name' => $child['name'],
                    'spaces' => $child['spaces'],
                ];
            }
            $tree[] = [
                'name' => $parent['name'],
                'spaces' => $parent['spaces'],
                'children' => $parent['children'],
            ];
        }

        $other = [];
        foreach ($byId as $spaceId => $space) {
            if (!isset($assignment[$spaceId])) {
                $other[] = $space;
            }
        }
        if ($other !== []) {
            self::sortSpaces($other);
            $tree[] = [
                'name' => Yii::t('ThiscoveryFeedModule.base', 'Other'),
                'spaces' => $other,
                'children' => [],
            ];
        }

        return $tree;
    }

    /**
     * @param Space[] $spaces
     * @return Space[]
     */
    public static function ordered(array $spaces): array
    {
        $tree = self::tree($spaces);
        if ($tree === null) {
            return $spaces;
        }

        $ordered = [];
        foreach ($tree as $parent) {
            foreach ($parent['spaces'] as $space) {
                $ordered[] = $space;
            }
            foreach ($parent['children'] as $child) {
                foreach ($child['spaces'] as $space) {
                    $ordered[] = $space;
                }
            }
        }

        return $ordered;
    }

    /**
     * @param array{sort: int, name: string} $a
     * @param array{sort: int, name: string} $b
     */
    private static function bySortThenName(array $a, array $b): int
    {
        $order = $a['sort'] <=> $b['sort'];

        return $order !== 0 ? $order : strcasecmp($a['name'], $b['name']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function rowRank(array $row): string
    {
        $parentSort = $row['parent_id'] !== null && $row['parent_id'] !== '' && $row['parent_name'] !== null
            ? (int) $row['parent_sort']
            : (int) $row['category_sort'];

        return sprintf('%010d-%010d-%010d', $parentSort, (int) $row['category_sort'], (int) $row['category_id']);
    }

    /**
     * @param Space[] $spaces
     */
    private static function sortSpaces(array &$spaces): void
    {
        usort($spaces, static function (Space $a, Space $b): int {
            $order = (int) $a->sort_order <=> (int) $b->sort_order;

            return $order !== 0 ? $order : strcasecmp((string) $a->name, (string) $b->name);
        });
    }
}
