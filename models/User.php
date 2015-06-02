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

    public function createUserFromCsv($student, $right){
        $this->SID = $student[0];
        $this->FirstName = $student[1];
        $this->LastName = $student[2];
        $this->email = $student[3];
        $this->rights = $right;
        $this->password = $student[7];
        $this->save();
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

    public static function findAllUsersArray($sortBy, $order)
    {
        return User::find()->orderBy([$sortBy => $order])->where(['rights' => 0 ])->asArray()->all();

    }

    public static function createStudentAccount($params)
    {
        $params['SID'] = $params['username'];
        $params['password'] = AppUtility::passwordHash($params['password']);
        $params['hideonpostswidget'] = '0';
        $user = new User();
        $user->attributes = $params;
        $user->save();

        if($user->id && isset($params['userid']) && isset($params['courseid']))
        {
            $student = new Student();
            $student->create($params);
        }

        if($user->id)
        {
            return true;
        }
        return false;
    }

    public static function findAllTeachers($sortBy, $order)
    {
        return User::find()->where(['rights' => [20,40,60,75,100]])->orderBy([$sortBy => $order])->asArray()->all();
    }

    public static function findUsers($params)
    {
        return User::findAll($params);
    }

    public static function updateRights($id, $rights, $groupId = 0)
    {
       $user = static::findOne(['id' =>$id]);
        $user->rights = $rights;
        $user->groupid = $groupId;
        $user->save();
    }

    public static function getById($id)
    {
        return static::findOne($id);
    }

    public static function getByIdAndCode($id, $code)
    {
        return static::findOne(['id' => $id, 'remoteaccess' => $code]);
    }

    public static function getByName($uname)
    {
        return static::findAll(['SID'=>$uname]);
    }

    public static function findAllById($id)
    {
        return static::find()->where(['id'=>$id])->asArray()->all();
    }

    public static function createAndEnrollNewStudent($params)
    {
        $params['SID'] = $params['username'];
        $params['password'] = AppUtility::passwordHash($params['password']);
        $params['rights'] = '10';
        $user = new User();
        $user->attributes = $params;
        $user->save();
        if($user->id)
        {
            return true;
        }
        return false;
    }
    public static function findByUserId($uid)
    {
        return static::findAll(['id'=>$uid]);
    }
    public static function updateImgByUserId($id){
        $user=User::getById($id);
        if($user->id=$id){
           $user->hasuserimg=1;
            $user->save();
        }
    }
    public static function deleteImgByUserId($id){
        $user=User::getById($id);
        if($user->id=$id){
            $user->hasuserimg=0;
            $user->save();
        }
    }

    public static function userAlreadyExist($StudentDataArray){
        $message = "Username {$StudentDataArray} already existed in system";
        return $message;
    }
}
