<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * The next events in the spaces a member belongs to.
 */
final class UpcomingEventService
{
    /**
     * @return array<int, array{title: string, url: string, when: string, space: string}>
     */
    public static function forUser(User $user, int $limit = 5): array
    {
        if (!Yii::$app->hasModule('calendar') || !class_exists(CalendarService::class)) {
            return [];
        }

        $containerIds = MemberSpaceService::contentContainerIds($user);
        if ($containerIds === []) {
            return [];
        }

        try {
            /** @var CalendarService $service */
            $service = Yii::$app->getModule('calendar')->get(CalendarService::class);
            $entries = $service->getUpcomingEntries(null, 120, 50);
        $items = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof CalendarEventIF) {
                continue;
            }
            if ($entry instanceof CalendarEntry && $entry->closed) {
                continue;
            }
            if (!$entry instanceof ContentActiveRecord || $entry->content === null) {
                continue;
            }
            if (!in_array((int) $entry->content->contentcontainer_id, $containerIds, true)) {
                continue;
            }
            if (!$entry->content->canView($user)) {
                continue;
            }

            $container = $entry->content->container;
            $start = $entry->getStartDateTime();
            $items[] = [
                'title' => (string) $entry->getTitle(),
                'url' => (string) $entry->getUrl(),
                'when' => $entry instanceof CalendarEntry && $entry->isAllDay()
                    ? Yii::$app->formatter->asDate($start, 'medium')
                    : Yii::$app->formatter->asDatetime($start, 'short'),
                'space' => $container instanceof Space ? $container->name : '',
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');

            return [];
        }
    }
}
