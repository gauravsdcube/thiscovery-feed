<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\models;

use humhub\modules\thiscoveryFeed\Module;
use yii\base\Model;

class SettingsForm extends Model
{
    /**
     * @var string
     */
    public $placement = Module::PLACEMENT_ALONGSIDE;

    /**
     * @var int
     */
    public $announcementLimit = 5;

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
            [['announcementLimit'], 'integer', 'min' => 1, 'max' => 10],
            [['widgets', 'order'], 'safe'],
        ];
    }
}
