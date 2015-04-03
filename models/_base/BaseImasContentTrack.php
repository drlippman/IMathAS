<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_content_track".
 *
 * @property string $id
 * @property string $userid
 * @property string $courseid
 * @property string $type
 * @property string $typeid
 * @property string $viewtime
 * @property string $info
 */
class BaseImasContentTrack extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_content_track';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'courseid', 'type', 'typeid', 'viewtime', 'info'], 'required'],
            [['userid', 'courseid', 'typeid', 'viewtime'], 'integer'],
            [['type', 'info'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'courseid' => 'Courseid',
            'type' => 'Type',
            'typeid' => 'Typeid',
            'viewtime' => 'Viewtime',
            'info' => 'Info',
        ];
    }
}
