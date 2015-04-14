<?php

namespace app\models;


use app\models\_base\BaseImasUsers;
use app\models\_base\BaseUsers;
use yii\db\ActiveRecord;

class User extends BaseImasUsers implements \yii\web\IdentityInterface
{

	public $username;
    public $authKey;
	
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //not implemented, but need to override the method of Identity Interface.
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user = static::findOne(['SID' => $username]);
        return $user;
    }


    public static function findUser($username)
    {
        $user = static::findOne(['SID' => $username]);
        if($user)
        {
            return $user;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }
}
