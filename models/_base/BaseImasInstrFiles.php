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
 *
 * @property ImasItems $item
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
            [['filename', 'itemid'], 'required'],
            [['itemid'], 'integer'],
            [['description'], 'string', 'max' => 254],
            [['filename'], 'string', 'max' => 1000]
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

    /**
     * @return \yii\db\ActiveQuery
     */
   /* public function getItem()
    {
        return $this->hasOne(BaseImasItems::className(), ['id' => 'itemid']);
    }*/

    public function getInlineText()
    {
        return $this->hasOne(BaseImasItems::className(), ['id' => 'itemid']);
    }
}
