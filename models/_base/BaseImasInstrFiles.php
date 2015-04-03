<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_instr_files".
 *
 * @property string $id
 * @property string $description
 * @property string $filename
 * @property string $itemid
 */
class BaseImasInstrFiles extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_instr_files';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description', 'filename', 'itemid'], 'required'],
            [['itemid'], 'integer'],
            [['description'], 'string', 'max' => 254],
            [['filename'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'description' => 'Description',
            'filename' => 'Filename',
            'itemid' => 'Itemid',
        ];
    }
}
