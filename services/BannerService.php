<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\file\models\File;
use humhub\modules\thiscoveryFeed\Module;
use Throwable;
use Yii;
use yii\web\UploadedFile;

/**
 * Stores the optional image shown across the top of the feed.
 */
final class BannerService
{
    public static function file(): ?File
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-feed');
        $guid = (string) $module->settings->get(Module::SETTING_BANNER_FILE, '');
        if ($guid === '') {
            return null;
        }

        $file = File::findOne(['guid' => $guid]);

        return $file instanceof File ? $file : null;
    }

    public static function url(): ?string
    {
        $file = self::file();
        if (!$file instanceof File) {
            return null;
        }

        try {
            return $file->getUrl([], false);
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');

            return null;
        }
    }

    public static function store(UploadedFile $uploaded): bool
    {
        $previous = self::file();
        $file = new File();
        $file->file_name = $uploaded->name;
        $file->mime_type = is_string($uploaded->type) && $uploaded->type !== '' ? $uploaded->type : 'application/octet-stream';
        $file->size = (int) $uploaded->size;
        $file->show_in_stream = 0;
        $file->public = 1;
        $file->standalone = 1;
        if (!$file->save()) {
            return false;
        }

        try {
            $file->setStoredFile($uploaded);
        } catch (Throwable $e) {
            Yii::error($e, 'thiscovery-feed');
            $file->delete();

            return false;
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-feed');
        $module->settings->set(Module::SETTING_BANNER_FILE, $file->guid);
        if ($previous instanceof File && $previous->id !== $file->id) {
            $previous->delete();
        }

        return true;
    }

    public static function delete(): void
    {
        $file = self::file();
        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-feed');
        $module->settings->delete(Module::SETTING_BANNER_FILE);
        if ($file instanceof File) {
            $file->delete();
        }
    }
}
