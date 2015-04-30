<?php

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasUsers;
use app\models\_base\BaseUsers;
use app\components\AppConstant;
use yii\db\ActiveRecord;
use Yii;

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

    public static function saveUserRecord($params)
    {
        $params = AppUtility::removeEmptyAttributes($params);
        $user = User::findByUsername(\Yii::$app->user->identity->SID);
        if(isset($params['password']))
        {
            $params['password'] = AppUtility::passwordHash($params['password']);
        }
        $user->attributes = $params;
        $user->save();
    }

    public static function findByEmail($email)
    {
        $user = static::findOne(['email' => $email]);
        return $user;
    }
    public static function findAllUser($sortBy, $order)
    {
        return User::find()->orderBy([$sortBy => $order])->all();
    }
    public static function findAllUsers($sortBy, $order)
    {
        return User::find()->orderBy([$sortBy => $order])->where(['rights' => [20,40,60,75,100] ])->all();
    }


    public static function createStudentAccount($params)
    {
        $params['SID'] = $params['username'];
        $params['password'] = AppUtility::passwordHash($params['password']);
        $params['hideonpostswidget'] = '0';
        $user = new User();
        $user->attributes = $params;
        $user->save();
        if($user->id)
        {
            return true;
        }
        return false;
    }


}
