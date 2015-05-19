<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_drillassess".
 *
 * @property string $id
 * @property string $name
 * @property string $summary
 * @property string $courseid
 * @property string $itemdescr
 * @property string $itemids
 * @property string $scoretype
 * @property integer $showtype
 * @property integer $n
 * @property string $classbests
 * @property integer $showtostu
 *
 * @property ImasCourses $course
 * @property ImasDrillassessSessions[] $imasDrillassessSessions
 */
class BaseImasDrillassess extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_drillassess';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'summary', 'courseid', 'itemdescr', 'itemids', 'scoretype', 'showtype', 'n', 'classbests', 'showtostu'], 'required'],
            [['summary', 'itemdescr', 'itemids', 'classbests'], 'string'],
            [['courseid', 'showtype', 'n', 'showtostu'], 'integer'],
            [['name'], 'string', 'max' => 254],
            [['scoretype'], 'string', 'max' => 3]
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
            'summary' => 'Summary',
            'courseid' => 'Courseid',
            'itemdescr' => 'Itemdescr',
            'itemids' => 'Itemids',
            'scoretype' => 'Scoretype',
            'showtype' => 'Showtype',
            'n' => 'N',
            'classbests' => 'Classbests',
            'showtostu' => 'Showtostu',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasDrillassessSessions()
    {
        return $this->hasMany(BaseImasDrillassessSessions::className(), ['drillassessid' => 'id']);
    }
}
