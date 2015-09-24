<?php

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasUsers;
use app\components\AppConstant;
use Yii;
use yii\db\Query;

class User extends BaseImasUsers implements \yii\web\IdentityInterface
{

    public $username;
    public $authKey;

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        //not implemented, but need to override the method of Identity Interface.
    }

    public static function findByUsername($username)
    {
        $user = static::findOne(['SID' => $username]);
        return $user;
    }

    public function createUserFromCsv($student, $right)
    {


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
        if ($user) {
            return $user;
        }
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    public static function saveUserRecord($params, $user)
    {

        $params = AppUtility::removeEmptyAttributes($params);
        if (isset($params['password'])) {
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
        return User::find()->orderBy([$sortBy => $order])->where(['rights' => [20, 40, 60, 75, 100]])->all();

    }

    public static function findAllUsersArray($sortBy, $order)
    {
        return User::find()->orderBy([$sortBy => $order])->where(['rights' => 0])->asArray()->all();

    }

    public static function createStudentAccount($params)
    {
        $params['SID'] = $params['username'];
        $params['password'] = AppUtility::passwordHash($params['password']);
        $params['hideonpostswidget'] = '0';
        $params['FirstName'] = $params['firstName'];
        $params['LastName'] = $params['lastName'];
        $user = new User();
        $user->attributes = $params;
        $user->save();
        if ($user->id && isset($params['userid']) && isset($params['courseid'])) {
            $student = new Student();
            $student->create($params);
        }
        if ($user->id) {
            return true;
        }
        return false;
    }

    public static function findAllTeachers($sortBy, $order)
    {
        return User::find()->where(['rights' => [20, 40, 60, 75, 100]])->orderBy([$sortBy => $order])->asArray()->all();
    }

    public static function findUsers($params)
    {
        return User::findAll($params);
    }

    public static function updateRights($id, $rights, $groupId = AppConstant::NUMERIC_ZERO)
    {
        $user = static::findOne(['id' => $id]);
        $user->rights = $rights;
        $user->groupid = $groupId;
        $user->save();
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function getByIdAndCode($id, $code)
    {
        return static::findOne(['id' => $id, 'remoteaccess' => $code]);
    }

    public static function getByName($uname)
    {
        return static::findOne(['SID' => $uname]);
    }

    public static function findAllById($id)
    {
        return static::find()->where(['id' => $id])->asArray()->all();
    }

    public function createAndEnrollNewStudent($params)
    {
        $this->SID = $params['username'];
        $this->FirstName = $params['FirstName'];
        $this->LastName = $params['LastName'];
        $this->email = $params['email'];
        $this->rights = '10';
        $this->password = AppUtility::passwordHash($params['password']);
        $this->msgnotify = '0';
        $this->save();
    }

    public static function findByUserId($uid)
    {
        return static::findOne(['id' => $uid]);
    }

    public static function updateImgByUserId($id)
    {
        $user = User::getById($id);
        if ($user->id = $id) {
            $user->hasuserimg = AppConstant::NUMERIC_ONE;
            $user->save();
        }
    }

    public static function deleteImgByUserId($id)
    {
        $user = User::getById($id);
        if ($user->id = $id) {
            $user->hasuserimg = AppConstant::NUMERIC_ZERO;
            $user->save();
        }
    }

    public static function userAlreadyExist($StudentDataArray)
    {
        $message = "Username {$StudentDataArray} already existed in system";
        return $message;
    }

    public static function findTeachersToList($courseId)
    {
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName'])
            ->from('imas_users')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_users.id = imas_teachers.userid'
            )
            ->where(['imas_teachers.courseid' => $courseId])
            ->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function saveGuestUserRecord($params)
    {
        $remainingData = AppUtility::removeEmptyAttributes($params);
        if (isset($remainingData['password'])) {
            $remainingData['password'] = AppUtility::passwordHash($remainingData['password']);
        }
        $this->attributes = $remainingData;
        $this->save();
        return $this->id;
    }

    public static function updateUser($now, $password, $userId)
    {
        $user = User::getById($userId);
        if ($user) {
            $user->lastaccess = $now;
            if ($password != '') {
                $user->password = $password;
            }
            $user->save();
        }
    }

    public static function studentGradebookData($courseId, $usersort)
    {
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName', 'imas_students.section'])
            ->from('imas_users')
            ->join('INNER JOIN',
                'imas_students',
                'imas_users.id = imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId]);

        if ($usersort == AppConstant::NUMERIC_ZERO) {
            $query->orderBy('imas_students.section', 'imas_users.LastName', 'imas_users.FirstName');
        } else {
            $query->orderBy('imas_users.LastName', 'imas_users.FirstName');
        }

        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    Public static function getByUserName($keyword)
    {
        $likeData = User::find()->select('FirstName')->where(['like', 'FirstName', $keyword])->all();
        return $likeData;
    }

    public static function getByUserIdAndStudentId($courseId)
    {
        $query = new Query();
        $query
            ->from('imas_users')

            ->join('INNER JOIN',
                'imas_students',
                'imas_users.id = imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public static function findStuForGroups($courseId)
    {

        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName', 'imas_users.hasuserimg'])
            ->from('imas_users')
            ->join('JOIN',
                'imas_students',
                'imas_users.id = imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public static function insertDataFroGroups($stuList)
    {
        $query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id IN ($stuList) ORDER BY LastName, FirstName";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function userDataForGroups($remove)
    {
        $query = new Query();
        $query->select(['FirstName', 'LastName'])
            ->from('imas_users')
            ->where(['id' => $remove]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getUserByIdAndGroupId($rights, $groupId, $orderBy)
    {
        $user = User::find()->where(['>', 'rights', $rights])->andWhere(['groupid' => $groupId])->orderBy($orderBy)->all();
        return $user;
    }

    public static function getUserByRights($rightsZero, $rightsTwelve, $orderBy)
    {
        $user = User::find()->where(['rights' => $rightsZero])->orWhere(['rights' => $rightsTwelve])->orderBy($orderBy)->all();
        return $user;
    }

    public static function getUserByLastNameSubstring($showusers, $orderBy)
    {
        $user = User::find()
            ->where('LastName LIKE :query')
            ->addParams([':query' => $showusers . '%'])
            ->all();
        return $user;
    }

    public static function getNameByIdUsingINClause($ids)
    {
        $query = "SELECT LastName,FirstName,id FROM imas_users WHERE id IN ($ids)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function findUserDataForIsolateAssessmentGrade($isTutor, $tutorsection, $aid, $cid, $hidelocked, $sortorder, $hassection)
    {
        $query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
        $query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid='$cid' ";
        $query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid='$aid' WHERE istu.courseid='$cid' ";
        if ($isTutor && isset($tutorsection) && $tutorsection != '') {
            $query .= " AND istu.section='$tutorsection' ";
        }
        if ($hidelocked) {
            $query .= ' AND istu.locked=0 ';
        }
        if ($hassection && $sortorder == "sec") {
            $query .= " ORDER BY istu.section,iu.LastName,iu.FirstName";
        } else {
            $query .= " ORDER BY iu.LastName,iu.FirstName";
        }
        $data = Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getListOfTeacher($groupId)
    {
        $query = "SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 AND groupid='$groupId' ORDER BY LastName,FirstName";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getTeacherData()
    {
        $query = "SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 ORDER BY LastName,FirstName";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getDataByJoin($data, $num)
    {
        $query = new Query();
        $query->select(['imas_users.*', 'imas_groups.name'])
            ->from('imas_users')
            ->join('LEFT JOIN', 'imas_groups',
                'imas_users.groupid=imas_groups.id');
        if ($num == AppConstant::NUMERIC_ZERO) {
            $query->where(['imas_users.SID' => $data]);
        } elseif ($num == AppConstant::NUMERIC_ONE) {
            $query->where(['imas_users.SID' => $data]);
        }

        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataByJoinForName($params)
    {
        $query = "SELECT imas_users.*,imas_groups.name FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE ";
        if (!empty($params['LastName'])) {
            $query .= "imas_users.LastName='{$params['LastName']}' ";
            if (!empty($params['FirstName'])) {
                $query .= "AND ";
            }
        }
        if (!empty($params['FirstName'])) {
            $query .= "imas_users.FirstName='{$params['FirstName']}' ";
        }
        $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function updateGroupId($id)
    {
        $Users = User::find()->where(['groupid' => $id])->all();
        if ($Users) {
            foreach ($Users as $User) {
                $User->groupid = AppConstant::NUMERIC_ZERO;
                $User->save();
            }
        }
    }

    public static function getByLastAccessAndRights($old)
    {
        $query = new Query();
        $query->select(['id'])
            ->from('imas_users')
            ->where(['rights' => 10])
            ->orWhere(['rights' => 0])->andWhere(['<', 'lastaccess', $old]);
        $command = $query->createCommand();
        $users = $command->queryAll();
        return $users;
    }

    public static function deleteByLastAccessAndRights($old)
    {
        $users = User::find()->where(['rights' => 10])->orWhere(['rights' => 0])->andWhere(['<', 'lastaccess', $old])->all();
        foreach ($users as $user) {
            $user->delete();
        }
    }

    public static function deleteUserByLastAccess($old)
    {
        $users = User::find()->where(['<', 'lastaccess', $old])->andWhere(['<', 'rights', 100])->all();
        foreach ($users as $user) {
            $user->delete();
        }
    }

    public static function getDistinctUserCount($date)
    {
        $query = new Query();
        $query->select('imas_users.id')
            ->distinct('imas_users.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere(['>', 'imas_users.lastaccess', $date]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);
    }

    public static function getStuCount($skipCid, $date, $skipCidS)
    {
        $query = new Query();
        $query->select('imas_students.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere(['>', 'imas_users.lastaccess', $date]);
        if (count($skipCid) > AppConstant::NUMERIC_ZERO) {
            $query->andWhere(['NOT IN', 'imas_students.courseid', $skipCidS]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);
    }

    public static function queryForStu($skipCid, $date, $skipCidS)
    {
        $query = new Query();
        $query->select('imas_users.id')
            ->distinct('imas_users.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere(['>', 'imas_users.lastaccess', $date]);
        if (count($skipCid) > AppConstant::NUMERIC_ZERO) {
            $query->andWhere(['NOT IN', 'imas_students.courseid', $skipCidS]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);

    }

    public static function getCountByJoin($skipCid, $date, $skipCidS)
    {
        $query = new Query();
        $query->select('imas_users.id')
            ->distinct('imas_users.id')
            ->from(['imas_users', 'imas_teachers'])
            ->where('imas_users.id=imas_teachers.userid')
            ->andWhere(['>', 'imas_users.lastaccess', $date]);
        if (count($skipCid) > AppConstant::NUMERIC_ZERO) {
            $query->andWhere(['NOT IN', 'imas_teachers.courseid', $skipCidS]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);
    }

    public static function getStuData($date)
    {
        $query = new Query();
        $query->select('imas_users.id')
            ->distinct('imas_users.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere(['>', 'imas_users.lastaccess', $date]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);
    }

    public static function getUserEmail($user)
    {
        $query = new Query();
        $query->select('email')
            ->from('imas_users')
            ->where(['>', 'rights', 20]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return count($data);
    }

    public static function getByRights()
    {
        $users = User::find()->select(['id', 'email', 'SID', 'rights'])->where(['rights' => 11])->orWhere(['rights' => 76])->orWhere(['rights' => 77])->all();
        return $users;
    }

    public static function updateLTIDomainCredentials($params)
    {
        $user = User::find()->where(['id' => $params['id']])->one();
        $user->email = $params['ltidomain'];
        $user->FirstName = $params['ltidomain'];
        $user->LastName = 'LTIcredential';
        $user->SID = $params['ltikey'];
        $user->password = $params['ltisecret'];
        $user->rights = $params['createinstr'];
        $user->groupid = $params['groupid'];
        $user->save();
    }

    public function createLTIDomainCredentials($params)
    {
        $this->email = $params['ltidomain'];
        $this->FirstName = $params['ltidomain'];
        $this->LastName = 'LTIcredential';
        $this->SID = $params['ltikey'];
        $this->password = $params['ltisecret'];
        $this->rights = $params['createinstr'];
        $this->groupid = $params['groupid'];
        $this->save();
    }

    public static function deleteUserById($id)
    {
        $user = User::find()->where(['id' => $id])->one();
        if ($user) {
            $user->delete();
        }
    }

    public static function updatePassword($md5pw, $id, $myRights, $groupid)
    {
        $query = "UPDATE imas_users SET password='$md5pw' WHERE id='{$id}'";
        if ($myRights < AppConstant::ADMIN_RIGHT) {
            $query .= " AND groupid='$groupid' AND rights<100";
        }
        Yii::$app->db->createCommand($query)->query();

    }

    public static function getUserDetailsByJoin($srch)
    {
        $query = "SELECT DISTINCT imas_users.*,imas_courses.id AS cid,imas_groups.name AS groupname FROM imas_users JOIN imas_courses ON imas_users.id=imas_courses.ownerid JOIN imas_groups ON imas_groups.id=imas_users.groupid WHERE imas_courses.id IN ";
        $query .= "(SELECT courseid FROM imas_inlinetext WHERE text LIKE '%$srch%') OR imas_courses.id IN ";
        $query .= "(SELECT courseid FROM imas_linkedtext WHERE text LIKE '%$srch%' OR summary LIKE '%$srch%') ORDER BY imas_groups.name,imas_users.LastName";
        return Yii::$app->db->createCommand($query)->query();

    }

    public static function getFirstNameAndLastName($toList)
    {
        $query = new Query();
        $query->select(['FirstName', 'LastName', 'email', 'id'])->from('imas_users')->where(['IN', 'id', $toList]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByUserRight($myRight, $groupId)
    {
        $query = new Query();
        $query->select(['id', 'FirstName', 'LastName'])
            ->from('imas_users')
            ->where(['>', 'rights', '19']);
        if ($myRight < AppConstant::ADMIN_RIGHT) {
            $query->andWhere(['groupid' => $groupId]);
        }
        $query->orderBy('LastName');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function updateUserForPendingReq($id)
    {
        $user = static::findOne(['id' => $id]);
        if ($user) {
            $user->rights = AppConstant::NUMERIC_TEN;
            $user->save();
        }
    }

    public static function getUserDataForUtilities($id)
    {
        $query = new Query();
        $query->select(['FirstName', 'SID', 'email'])->from('imas_users')->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;

    }

    public static function findPendingUser($offset)
    {

        $query = new Query();
        $query->select(['id', 'SID', 'LastName', 'FirstName', 'email'])
            ->from('imas_users')
            ->where(['=', 'rights', '0'])
            ->orWhere(['=', 'rights', '12'])
            ->limit(AppConstant::NUMERIC_ONE)
            ->offset($offset);
        $command = $query->createCommand();
        $data = $command->queryone();
        return $data;
    }

    public static function getUserGreaterThenTeacherRights()
    {
        return User::find()->select('id, FirstName, LastName')->where(['>=', 'rights', AppConstant::TEACHER_RIGHT])->orderBy('LastName,FirstName')->all();
    }

    public static function getByUserIdASDiagnoId($params)
    {
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.groupid'])
            ->from('imas_users')
            ->join('JOIN',
                'imas_diags',
                'imas_users.id=imas_diags.ownerid'
            )
            ->andWhere(['imas_diags.id' => $params['id']]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function getPassword($diagSID)
    {
        return User::find()->select('password')->where(['SID' => $diagSID])->all();
    }

    public static function getBySId($diagSID)
    {
        return User::find()->select('id')->where(['SID' => $diagSID])->all();
    }

    public static function setLastAccess($now, $userid)
    {
        $user = static::findOne(['id' => $userid]);
        $user->lastaccess = $now;
        $user->save();
    }

    public function addUser($diagSID, $passwd, $ten, $firstname, $lastname, $eclass, $now)
    {
        $this->SID = $diagSID;
        $this->password = $passwd;
        $this->rights = $ten;
        $this->FirstName = $firstname;
        $this->LastName = $lastname;
        $this->email = $eclass;
        $this->lastaccess = $now;
        $this->save();
        return $this->id;
    }

    public static function getByIdOrdered()
    {
        return User::find()->select('id, FirstName, LastName')->where(['>=', 'rights', 19])->orderBy('LastName,FirstName')->all();
    }

    public static function getByGroupId($id)
    {
        return User::find()->select('groupid')->where(['id' => $id])->all();
    }
    public static function userDataForTutorial($id)
    {
        $query = new Query();
        $query->select(['FirstName', 'LastName'])
            ->from('imas_users')
            ->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function getPwdUNameById($id)
    {
        return User::find()->select('password,LastName,FirstName')->where(['id' => $id])->all();
    }

    public static function getStudentData($curids,$id){
        $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_students ";
        $query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$id' ";
        $query .= "AND imas_users.id NOT IN ($curids) ORDER BY imas_users.LastName,imas_users.FirstName";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
}

