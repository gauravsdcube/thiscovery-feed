<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\libs\UUID;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\stream\WallStreamEntryOptions;
use humhub\modules\content\widgets\WallCreateContentForm;
use humhub\modules\file\models\File;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\stream\actions\StreamEntryResponse;
use humhub\modules\user\models\User;
use humhub\modules\topic\models\Topic;
use Throwable;
use Yii;

/**
 * Publishes one feed post into each chosen space.
 *
 * HumHub keeps a post inside a single space, so each destination gets its own
 * post. Files are copied so every space receives them.
 */
final class FeedPublisher
{
    /** @var array<int, mixed> */
    private array $originalTopics = [];

    /**
     * @param Space[] $spaces
     * @return array<string, mixed>
     */
    public function publish(array $spaces): array
    {
        if ($spaces === []) {
            return ['errors' => ['message' => [Yii::t('ThiscoveryFeedModule.base', 'Choose at least one space.')]]];
        }

        $this->originalTopics = $this->requestTopics();

        $probe = new Post($spaces[0]);
        $probe->load(Yii::$app->request->post(), 'Post');
        $originalGuids = $this->fileGuids();
        if ($originalGuids !== []) {
            $probe->scenario = Post::SCENARIO_HAS_FILES;
        }
        if (!$probe->validate()) {
            return ['errors' => $probe->getErrors()];
        }

        $files = $this->ownedFiles($originalGuids);
        $shareId = UUID::v4();
        $created = [];
        $first = null;

        foreach ($spaces as $index => $space) {
            $guids = $index === 0 ? $originalGuids : $this->copyGuids($files);
            $result = $this->publishOne($space, $guids);
            if (isset($result['errors'])) {
                if ($created === []) {
                    return $result;
                }
                Yii::warning('Feed post could not be copied to space ' . $space->guid, 'thiscovery-feed');
                continue;
            }
            $first ??= $result;
            if (isset($result['id']) && is_numeric($result['id'])) {
                $created[] = (int) $result['id'];
            }
        }

        if ($first === null) {
            return ['errors' => ['message' => [Yii::t('ThiscoveryFeedModule.base', 'The post could not be published.')]]];
        }

        if (count($created) < 2) {
            return $first;
        }

        foreach ($created as $contentId) {
            FeedShareService::remember($shareId, $contentId);
        }

        $content = Content::findOne($created[0]);
        if (!$content instanceof Content) {
            return $first;
        }

        return StreamEntryResponse::getAsArray(
            $content,
            (new WallStreamEntryOptions())->viewContext(WallStreamEntryOptions::VIEW_CONTEXT_DASHBOARD),
        );
    }

    /**
     * One profile post, which is what the dashboard composer creates.
     *
     * @return array<string, mixed>
     */
    public function publishToProfile(User $user): array
    {
        $this->originalTopics = $this->requestTopics();

        $probe = new Post($user);
        $probe->load(Yii::$app->request->post(), 'Post');
        $guids = $this->fileGuids();
        if ($guids !== []) {
            $probe->scenario = Post::SCENARIO_HAS_FILES;
        }
        if (!$probe->validate()) {
            return ['errors' => $probe->getErrors()];
        }

        return $this->publishOne($user, $guids);
    }

    /**
     * @param string[] $guids
     * @return array<string, mixed>
     */
    private function publishOne(ContentContainerActiveRecord $container, array $guids): array
    {
        $body = Yii::$app->request->getBodyParams();
        $body['fileList'] = $guids;
        $body['postTopicInput'] = $this->topicsFor($container);
        Yii::$app->request->setBodyParams($body);

        $post = new Post($container);
        $post->load(Yii::$app->request->post(), 'Post');
        if ($guids !== []) {
            $post->scenario = Post::SCENARIO_HAS_FILES;
        }

        try {
            $result = WallCreateContentForm::create($post, $container);
        } catch (Throwable $e) {
            Yii::error($e, 'thiscovery-feed');
            if ($post->content && $post->content->id) {
                return $this->entryResponse($post->content);
            }

            return ['errors' => ['message' => [Yii::t('ThiscoveryFeedModule.base', 'The post could not be published.')]]];
        }

        return is_array($result) ? $result : ['errors' => ['message' => [Yii::t('ThiscoveryFeedModule.base', 'The post could not be published.')]]];
    }

    /**
     * @return string[]
     */
    private function fileGuids(): array
    {
        $raw = Yii::$app->request->post('fileList', []);
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        $guids = [];
        foreach ($raw as $guid) {
            if (is_string($guid) && $guid !== '') {
                $guids[] = $guid;
            }
        }

        return $guids;
    }

    /**
     * @param string[] $guids
     * @return File[]
     */
    private function ownedFiles(array $guids): array
    {
        if ($guids === [] || !Yii::$app->hasModule('file')) {
            return [];
        }

        $files = [];
        foreach (File::find()->where(['guid' => $guids, 'created_by' => Yii::$app->user->id])->all() as $file) {
            if ($file instanceof File && !$file->standalone) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @param File[] $files
     * @return string[]
     */
    private function copyGuids(array $files): array
    {
        $guids = [];
        foreach ($files as $file) {
            $copy = $this->copyFile($file);
            if ($copy !== null) {
                $guids[] = $copy->guid;
            }
        }

        return $guids;
    }

    private function copyFile(File $source): ?File
    {
        try {
            $content = $source->getStore()->getContent();
            if ($content === '') {
                Yii::warning('Feed could not read a file to copy it into another space.', 'thiscovery-feed');
                return null;
            }

            $copy = new File();
            $copy->file_name = $source->file_name;
            $copy->mime_type = $source->mime_type;
            $copy->size = (int) $source->size;
            $copy->title = $source->title;
            $copy->show_in_stream = $source->show_in_stream;
            $copy->hash_sha1 = $this->fileHash($source);
            if (!$copy->save()) {
                return null;
            }
            $copy->getStore()->setContent($content);

            return $copy;
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');
            return null;
        }
    }

    /**
     * The copied file must keep the source checksum. Building the stream card
     * asks for that checksum, and the storage used here has no local filesystem
     * handle to calculate it again.
     */
    private function fileHash(File $source): ?string
    {
        if (is_string($source->hash_sha1) && $source->hash_sha1 !== '') {
            return $source->hash_sha1;
        }

        $store = $source->getStore();
        if (!method_exists($store, 'checksum')) {
            return null;
        }

        $hash = $store->checksum();

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function entryResponse(Content $content): array
    {
        try {
            return StreamEntryResponse::getAsArray(
                $content,
                (new WallStreamEntryOptions())->viewContext(WallStreamEntryOptions::VIEW_CONTEXT_DASHBOARD),
            );
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');

            return ['id' => (int) $content->id];
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function requestTopics(): array
    {
        $topics = Yii::$app->request->post('postTopicInput', []);
        if (!is_array($topics)) {
            return ($topics === null || $topics === '') ? [] : [$topics];
        }

        return $topics;
    }

    /**
     * Topics belong to one space. New topic names are created in each destination.
     * Existing topics are kept only when they already belong to that space.
     *
     * @return string[]
     */
    private function topicsFor(ContentContainerActiveRecord $container): array
    {
        if (!Yii::$app->hasModule('topic')) {
            return [];
        }

        $kept = [];
        foreach ($this->originalTopics as $topic) {
            if (is_string($topic) && str_starts_with($topic, '_add:')) {
                $kept[] = $topic;
                continue;
            }
            if (!is_numeric($topic)) {
                continue;
            }
            $model = Topic::findOne((int) $topic);
            if ($model && (int) $model->contentcontainer_id === (int) $container->contentcontainer_id) {
                $kept[] = (string) $topic;
            }
        }

        return $kept;
    }
}
