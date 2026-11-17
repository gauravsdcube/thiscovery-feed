<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Pinned posts from the member's spaces, for the announcements rail.
 */
final class AnnouncementService
{
    /**
     * @return array<int, array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, excerpt: string, time: string}>
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
                'content.state' => Content::STATE_PUBLISHED,
            ])
            ->andWhere(['or', ['content.archived' => 0], ['content.archived' => null]])
            ->orderBy(['content.stream_sort_date' => SORT_DESC])
            ->limit($limit * 2)
            ->all();

        $items = [];
        $seenShares = [];
        foreach ($contents as $content) {
            if (!$content instanceof Content || !$content->canView($user)) {
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
     * @return array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, excerpt: string, time: string}|null
     */
    private static function present(Content $content): ?array
    {
        try {
            $model = $content->getModel();
            $container = $content->getContainer();
            $excerpt = $model ? self::excerpt($model) : '';
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');
            return null;
        }

        if ($model === null || !$container instanceof Space) {
            return null;
        }
        $title = '';
        if (method_exists($model, 'hasAttribute') && $model->hasAttribute('title') && is_string($model->title) && trim($model->title) !== '') {
            $title = trim($model->title);
        }
        if ($title === '' && $excerpt !== '') {
            $title = $excerpt;
            $excerpt = '';
        }
        if ($title === '') {
            $title = $model->getContentName();
        }

        $when = $content->created_at ?: $content->updated_at;

        $spaces = [];
        foreach (FeedShareService::spaces($content) as $space) {
            $spaces[] = [
                'name' => $space->name,
                'url' => $space->getUrl(),
            ];
        }

        return [
            'url' => $content->getUrl(),
            'spaces' => $spaces,
            'title' => $title,
            'excerpt' => $excerpt,
            'time' => $when ? Yii::$app->formatter->asRelativeTime($when) : '',
        ];
    }

    private static function excerpt(object $model): string
    {
        $text = '';
        if (method_exists($model, 'hasAttribute') && $model->hasAttribute('message') && is_string($model->message) && $model->message !== '') {
            $text = RichTextToPlainTextConverter::process($model->message);
        } elseif (method_exists($model, 'getContentDescription')) {
            $text = (string) $model->getContentDescription();
        }

        $text = strip_tags($text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[#*_`~>]+/u', ' ', $text) ?? $text;
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) > 160) {
            $text = rtrim(mb_substr($text, 0, 157)) . '…';
        }

        return $text;
    }
}
