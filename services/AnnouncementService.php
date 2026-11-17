<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\content\models\Content;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryFeed\models\FeedShare;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Pinned posts from the member's spaces, for the announcements rail.
 */
final class AnnouncementService
{
    /**
     * @return array<int, array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, message: string, record: Post, time: string}>
     */
    public static function forUser(User $user, int $limit): array
    {
        $containerIds = MemberSpaceService::contentContainerIds($user);
        if ($containerIds === []) {
            return [];
        }

        $limit = max(1, min(10, $limit));
        $contents = Content::find()
            ->where([
                'content.contentcontainer_id' => $containerIds,
                'content.pinned' => 1,
                'content.object_model' => Post::class,
                'content.state' => Content::STATE_PUBLISHED,
            ])
            ->andWhere(['or', ['content.archived' => 0], ['content.archived' => null]])
            ->orderBy(['content.stream_sort_date' => SORT_DESC])
            ->limit($limit * 2)
            ->all();

        $items = [];
        $seenShares = [];
        foreach ($contents as $content) {
            if (!$content instanceof Content || !$content->isPinned() || !$content->canView($user)) {
                continue;
            }
            $shareId = FeedShareService::shareId((int) $content->id);
            if ($shareId !== null) {
                if (isset($seenShares[$shareId])) {
                    continue;
                }
                $seenShares[$shareId] = true;
            }

            $item = self::present($content);
            if ($item === null) {
                continue;
            }

            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, message: string, record: Post, time: string}|null
     */
    private static function present(Content $content): ?array
    {
        try {
            $model = $content->getModel();
            $container = $content->getContainer();
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');
            return null;
        }

        if (!$model instanceof Post || !$container instanceof Space) {
            return null;
        }

        $title = is_string($model->title) ? trim($model->title) : '';
        $when = $content->created_at ?: $content->updated_at;

        return [
            'url' => $content->getUrl(),
            'spaces' => self::pinnedSpaceLinks($content),
            'title' => $title,
            'message' => is_string($model->message) ? $model->message : '',
            'record' => $model,
            'time' => $when ? Yii::$app->formatter->asRelativeTime($when) : '',
        ];
    }

    /**
     * A post sent to several spaces is listed once, under the spaces where that copy is pinned.
     *
     * @return array<int, array{name: string, url: string}>
     */
    private static function pinnedSpaceLinks(Content $content): array
    {
        $copies = [$content];
        $shareId = FeedShareService::shareId((int) $content->id);
        if ($shareId !== null) {
            $ids = FeedShare::find()->select('content_id')->where(['share_id' => $shareId])->column();
            $copies = Content::find()->where(['id' => $ids, 'pinned' => 1])->all();
        }

        $spaces = [];
        foreach ($copies as $copy) {
            if (!$copy instanceof Content || !$copy->isPinned()) {
                continue;
            }
            $container = $copy->getContainer();
            if (!$container instanceof Space) {
                continue;
            }
            $spaces[] = [
                'name' => $container->name,
                'url' => $container->getUrl(),
            ];
        }

        return $spaces;
    }
}
