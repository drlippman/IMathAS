<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_msgs".
 *
 * @property string $id
 * @property string $courseid
 * @property string $title
 * @property string $message
 * @property string $msgto
 * @property string $msgfrom
 * @property string $senddate
 * @property integer $isread
 * @property integer $replied
 * @property string $parent
 * @property string $baseid
 *
 * @property ImasUsers $msgfrom0
 * @property ImasUsers $msgto0
 */
class BaseImasMsgs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_msgs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'title', 'message', 'msgto', 'msgfrom'], 'required'],
            [['courseid', 'msgto', 'msgfrom', 'senddate', 'isread', 'replied', 'parent', 'baseid'], 'integer'],
            [['message'], 'string'],
            [['title'], 'string', 'max' => 254]
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
            'title' => 'Title',
            'message' => 'Message',
            'msgto' => 'Msgto',
            'msgfrom' => 'Msgfrom',
            'senddate' => 'Senddate',
            'isread' => 'Isread',
            'replied' => 'Replied',
            'parent' => 'Parent',
            'baseid' => 'Baseid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMsgfrom()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'msgfrom']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMsgto()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'msgto']);
    }

    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
