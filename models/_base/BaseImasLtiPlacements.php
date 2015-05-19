<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_lti_placements".
 *
 * @property string $id
 * @property string $org
 * @property string $contextid
 * @property string $linkid
 * @property string $typeid
 * @property string $placementtype
 */
class BaseImasLtiPlacements extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_lti_placements';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['org', 'contextid', 'linkid', 'typeid', 'placementtype'], 'required'],
            [['typeid'], 'integer'],
            [['org', 'contextid', 'linkid'], 'string', 'max' => 255],
            [['placementtype'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'org' => 'Org',
            'contextid' => 'Contextid',
            'linkid' => 'Linkid',
            'typeid' => 'Typeid',
            'placementtype' => 'Placementtype',
        ];
    }
}
