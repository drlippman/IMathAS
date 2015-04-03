<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_courses".
 *
 * @property string $id
 * @property string $ownerid
 * @property string $name
 * @property string $enrollkey
 * @property string $itemorder
 * @property integer $hideicons
 * @property integer $allowunenroll
 * @property integer $copyrights
 * @property string $blockcnt
 * @property integer $msgset
 * @property integer $toolset
 * @property integer $chatset
 * @property integer $showlatepass
 * @property string $topbar
 * @property integer $cploc
 * @property integer $available
 * @property string $lockaid
 * @property string $theme
 * @property integer $latepasshrs
 * @property integer $picicons
 * @property integer $newflag
 * @property integer $istemplate
 * @property integer $deflatepass
 * @property string $deftime
 * @property string $outcomes
 * @property string $ancestors
 * @property string $ltisecret
 */
class BaseImasCourses extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_courses';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ownerid', 'hideicons', 'allowunenroll', 'copyrights', 'blockcnt', 'msgset', 'toolset', 'chatset', 'showlatepass', 'cploc', 'available', 'lockaid', 'latepasshrs', 'picicons', 'newflag', 'istemplate', 'deflatepass', 'deftime'], 'integer'],
            [['name', 'enrollkey', 'itemorder', 'outcomes', 'ancestors', 'ltisecret'], 'required'],
            [['itemorder', 'outcomes', 'ancestors'], 'string'],
            [['name'], 'string', 'max' => 254],
            [['enrollkey'], 'string', 'max' => 100],
            [['topbar', 'theme'], 'string', 'max' => 32],
            [['ltisecret'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ownerid' => 'Ownerid',
            'name' => 'Name',
            'enrollkey' => 'Enrollkey',
            'itemorder' => 'Itemorder',
            'hideicons' => 'Hideicons',
            'allowunenroll' => 'Allowunenroll',
            'copyrights' => 'Copyrights',
            'blockcnt' => 'Blockcnt',
            'msgset' => 'Msgset',
            'toolset' => 'Toolset',
            'chatset' => 'Chatset',
            'showlatepass' => 'Showlatepass',
            'topbar' => 'Topbar',
            'cploc' => 'Cploc',
            'available' => 'Available',
            'lockaid' => 'Lockaid',
            'theme' => 'Theme',
            'latepasshrs' => 'Latepasshrs',
            'picicons' => 'Picicons',
            'newflag' => 'Newflag',
            'istemplate' => 'Istemplate',
            'deflatepass' => 'Deflatepass',
            'deftime' => 'Deftime',
            'outcomes' => 'Outcomes',
            'ancestors' => 'Ancestors',
            'ltisecret' => 'Ltisecret',
        ];
    }
}
