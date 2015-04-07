<?php

namespace app\models;


use app\models\_base\BaseImasUsers;
use app\models\_base\BaseUsers;
use yii\db\ActiveRecord;

class User extends BaseImasUsers implements \yii\web\IdentityInterface
{

	public $username;
	
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
  //      echo'dfgfg';die;
        //  return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //not implemented
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
//        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
 //       return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }
}
