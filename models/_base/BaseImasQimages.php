<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_qimages".
 *
 * @property string $id
 * @property string $qsetid
 * @property string $var
 * @property string $filename
 * @property string $alttext
 */
class BaseImasQimages extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_qimages';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['qsetid', 'var', 'filename', 'alttext'], 'required'],
            [['qsetid'], 'integer'],
            [['var'], 'string', 'max' => 50],
            [['filename'], 'string', 'max' => 100],
            [['alttext'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'qsetid' => 'Qsetid',
            'var' => 'Var',
            'filename' => 'Filename',
            'alttext' => 'Alttext',
        ];
    }
}
