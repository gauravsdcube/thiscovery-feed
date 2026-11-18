<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\models;

use humhub\modules\thiscoveryFeed\Module;
use yii\base\Model;
use yii\web\UploadedFile;

class SettingsForm extends Model
{
    /**
     * @var string
     */
    public $placement = Module::PLACEMENT_ALONGSIDE;

    /**
     * @var bool
     */
    public $showInTopMenu = true;

    /**
     * @var int
     */
    public $announcementLimit = 5;

    /**
     * @var int
     */
    public $announcementLines = 5;

    /**
     * @var bool
     */
    public $showBanner = false;

    /**
     * @var UploadedFile|null
     */
    public $bannerFile;

    /**
     * @var bool
     */
    public $removeBanner = false;

    /**
     * @var string[]
     */
    public $widgets = [];

    /**
     * @var array<string, mixed>
     */
    public $order = [];

    public function rules()
    {
        return [
            [['placement'], 'required'],
            [['placement'], 'in', 'range' => [Module::PLACEMENT_ALONGSIDE, Module::PLACEMENT_REPLACE]],
            [['showInTopMenu'], 'boolean'],
            [['announcementLimit'], 'integer', 'min' => 1, 'max' => 10],
            [['announcementLines'], 'integer', 'min' => 1, 'max' => 20],
            [['showBanner', 'removeBanner'], 'boolean'],
            [['bannerFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'], 'maxSize' => 8 * 1024 * 1024, 'checkExtensionByMimeType' => true],
            [['widgets', 'order'], 'safe'],
        ];
    }
}
