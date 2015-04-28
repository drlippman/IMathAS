<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_library_items".
 *
 * @property string $id
 * @property string $libid
 * @property string $qsetid
 * @property string $ownerid
 * @property integer $junkflag
 *
 * @property ImasQuestionset $qset
 */
class BaseImasLibraryItems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_library_items';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['libid', 'qsetid', 'ownerid'], 'required'],
            [['libid', 'qsetid', 'ownerid', 'junkflag'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'libid' => 'Libid',
            'qsetid' => 'Qsetid',
            'ownerid' => 'Ownerid',
            'junkflag' => 'Junkflag',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQset()
    {
        return $this->hasOne(BaseImasQuestionset::className(), ['id' => 'qsetid']);
    }
}
