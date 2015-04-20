<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_diags".
 *
 * @property string $id
 * @property string $ownerid
 * @property string $name
 * @property string $term
 * @property integer $public
 * @property string $cid
 * @property string $idprompt
 * @property string $ips
 * @property string $pws
 * @property string $sel1name
 * @property string $sel1list
 * @property string $aidlist
 * @property string $sel2name
 * @property string $sel2list
 * @property string $entryformat
 * @property string $forceregen
 * @property integer $reentrytime
 *
 * @property ImasUsers $owner
 */
class BaseImasDiags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_diags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['ownerid', 'public', 'cid', 'forceregen', 'reentrytime'], 'integer'],
            [['ips', 'pws', 'sel1list', 'aidlist', 'sel2list'], 'string'],
            [['name', 'idprompt', 'sel1name', 'sel2name'], 'string', 'max' => 254],
            [['term'], 'string', 'max' => 10],
            [['entryformat'], 'string', 'max' => 3]
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
            'term' => 'Term',
            'public' => 'Public',
            'cid' => 'Cid',
            'idprompt' => 'Idprompt',
            'ips' => 'Ips',
            'pws' => 'Pws',
            'sel1name' => 'Sel1name',
            'sel1list' => 'Sel1list',
            'aidlist' => 'Aidlist',
            'sel2name' => 'Sel2name',
            'sel2list' => 'Sel2list',
            'entryformat' => 'Entryformat',
            'forceregen' => 'Forceregen',
            'reentrytime' => 'Reentrytime',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(ImasUsers::className(), ['id' => 'ownerid']);
    }
}
