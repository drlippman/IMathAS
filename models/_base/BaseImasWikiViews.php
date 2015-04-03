<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_wiki_views".
 *
 * @property string $id
 * @property string $userid
 * @property string $wikiid
 * @property string $lastview
 * @property string $stugroupid
 */
class BaseImasWikiViews extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_wiki_views';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'wikiid', 'lastview'], 'required'],
            [['userid', 'wikiid', 'lastview', 'stugroupid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'wikiid' => 'Wikiid',
            'lastview' => 'Lastview',
            'stugroupid' => 'Stugroupid',
        ];
    }
}
