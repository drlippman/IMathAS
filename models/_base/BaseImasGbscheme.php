<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_gbscheme".
 *
 * @property string $id
 * @property string $courseid
 * @property integer $useweights
 * @property integer $orderby
 * @property string $defaultcat
 * @property integer $defgbmode
 * @property integer $stugbmode
 * @property integer $usersort
 * @property string $colorize
 *
 * @property ImasCourses $course
 */
class BaseImasGbscheme extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_gbscheme';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            ['courseid', 'required'],
            [['courseid', 'useweights', 'orderby', 'defgbmode', 'stugbmode', 'usersort'], 'integer'],
            [['defaultcat'], 'string', 'max' => 254],
            [['colorize'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'courseid' => 'Courseid',
            'useweights' => 'Useweights',
            'orderby' => 'Orderby',
            'defaultcat' => 'Defaultcat',
            'defgbmode' => 'Defgbmode',
            'stugbmode' => 'Stugbmode',
            'usersort' => 'Usersort',
            'colorize' => 'Colorize',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
