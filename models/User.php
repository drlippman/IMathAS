<?php

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasUsers;
use app\models\_base\BaseUsers;
use app\components\AppConstant;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Query;

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
        return $this->id;
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

    public static function saveUserRecord($params, $user)
    {

        $params = AppUtility::removeEmptyAttributes($params);
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
        return static::findOne(['SID'=>$uname]);
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
        return static::findOne(['id'=>$uid]);
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
    public static function findTeachersToList($courseId)
    {
        $query = new Query();
        $query	->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName'])
            ->from('imas_users')
            ->join(	'INNER JOIN',
                'imas_teachers',
                'imas_users.id = imas_teachers.userid'
            )
            ->where(['imas_teachers.courseid' => $courseId])
            ->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function saveGuestUserRecord($params){
        $remainingData = AppUtility::removeEmptyAttributes($params);
        if(isset($remainingData['password']))
        {
            $remainingData['password'] = AppUtility::passwordHash($remainingData['password']);
        }
        $this->attributes = $remainingData;
        $this->save();
        return $this->id;
    }

    public static function updateUser($now,$password,$userId){
        $user = User::getById($userId);
        if ($user){
            $user->lastaccess = $now;
            if($password !=''){
                $user->password = $password;
            }
            $user->save();
        }
    }

    public static function studentGradebookData($courseId,$usersort)
    {
        $query = new Query();
        $query	->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName','imas_students.section'])
            ->from('imas_users')
            ->join(	'INNER JOIN',
                'imas_students',
                'imas_users.id = imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId]);

          if ($usersort==0) {
              $query ->orderBy('imas_students.section','imas_users.LastName','imas_users.FirstName');
        } else {
              $query ->orderBy('imas_users.LastName','imas_users.FirstName');
        }

        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}
