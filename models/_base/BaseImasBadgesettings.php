<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_badgesettings".
 *
 * @property string $id
 * @property string $name
 * @property string $badgetext
 * @property string $description
 * @property string $longdescription
 * @property string $courseid
 * @property string $requirements
 */
class BaseImasBadgesettings extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_badgesettings';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'badgetext', 'description', 'longdescription', 'courseid', 'requirements'], 'required'],
            [['longdescription', 'requirements'], 'string'],
            [['courseid'], 'integer'],
            [['name', 'description'], 'string', 'max' => 128],
            [['badgetext'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'badgetext' => 'Badgetext',
            'description' => 'Description',
            'longdescription' => 'Longdescription',
            'courseid' => 'Courseid',
            'requirements' => 'Requirements',
        ];
    }
}
