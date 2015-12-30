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
        $user = User::findOne(['SID' => $username]);
        if ($user)
        {
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

    public function saveUserRecord($params, $user)
    {
        $params = AppUtility::removeEmptyAttributes($params);
        if (isset($params['password'])) {
            $params['password'] = AppUtility::passwordHash($params['password']);
        }
        $this->attributes = $params;
        $this->save();
    }

    public static function findByEmail($email)
    {
        return User::find()->where(['email' => $email])->asArray()->all();
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
         $data = User::findOne(['id' => $id]);
         return $data;
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

    public static function findTeachersToList($courseId)
    {
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName'])
            ->from('imas_users')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_users.id = imas_teachers.userid'
            )
            ->where('imas_teachers.courseid= :courseId')
            ->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->bindValue('courseId',$courseId)->queryAll();
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
            ->where('imas_students.courseid = :courseId');
        if ($usersort == AppConstant::NUMERIC_ZERO) {
            $query->orderBy('imas_students.section', 'imas_users.LastName', 'imas_users.FirstName');
        } else {
            $query->orderBy('imas_users.LastName', 'imas_users.FirstName');
        }
        $command = $query->createCommand();
        $data = $command->bindValue(':courseId',$courseId)->queryAll();
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
            ->where('imas_students.courseid = :courseId');
        $command = $query->createCommand();
        $data = $command->bindValue(':courseId',$courseId)->queryAll();
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
            ->where('imas_students.courseid = :courseId');
        $command = $query->createCommand();
        $data = $command->bindValue(':courseId',$courseId)->queryAll();
        return $data;

    }

    public static function insertDataFroGroups($stuList)
    {
        return User::find()->select('FirstName,LastName,SID')->where(['IN','id',$stuList])->orderBy('LastName,FirstName')->all();
    }

    public static function userDataForGroups($remove)
    {
        $query = new Query();
        $query->select(['FirstName', 'LastName'])
            ->from('imas_users')
            ->where('id = :remove');
        $command = $query->createCommand();
        $data = $command->bindValue(':remove',$remove)->queryAll();
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
        return User::find()->select('LastName,FirstName,id')->where(['IN','id',$ids])->all();
    }

    public static function findUserDataForIsolateAssessmentGrade($isTutor, $tutorsection, $aid, $cid, $hidelocked, $sortorder, $hassection)
    {
        $query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
        $query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid= :courseId ";
        $query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid= :assessmentId WHERE istu.courseid= :courseId ";
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
        $data = Yii::$app->db->createCommand($query)->bindValues([':courseId' => $cid, 'assessmentId'=> $aid])->queryAll();
        return $data;
    }

    public static function getListOfTeacher($groupId)
    {
        return User::find()->select('id,LastName,FirstName,SID')->where(['>','rights',10])->andWhere(['groupid' => $groupId])->orderBy('LastName,FirstName')->all();
    }

    public static function getTeacherData()
    {
        return User::find()->select('id,LastName,FirstName,SID')->where(['>','rights',10])->orderBy('LastName , FirstName')->all();
    }

    public static function getDataByJoin($data, $num)
    {
        $query = new Query();
        $query->select(['imas_users.*', 'imas_groups.name'])
            ->from('imas_users')
            ->join('LEFT JOIN',
                'imas_groups',
                'imas_users.groupid=imas_groups.id');
        if ($num == AppConstant::NUMERIC_ZERO) {
            $query->andWhere('imas_users.SID=:data', [':data' => $data]);
        } elseif ($num == AppConstant::NUMERIC_ONE) {
            $query->andWhere('imas_users.email=:data', [':data' => $data]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataByJoinForName($params)
    {
        $lastName = $params['LastName'];
        $firstName = $params['FirstName'];
        $query = new Query();
        $query->select('imas_users.*,imas_groups.name')->from('imas_users')
            ->join('LEFT JOIN','imas_groups',
                'imas_users.groupid=imas_groups.id');
        if (!empty($lastName))
        {
            $query->andWhere('imas_users.LastName=:lastName',[':lastName' => $lastName]);
        }
        if (!empty($firstName))
        {
            $query->andWhere('imas_users.FirstName=:firstName',[':firstName' => $firstName]);
        }
        $query->orderBy('imas_users.LastName,imas_users.FirstName');
        $command =  $query->createCommand();
        $data =  $command->queryAll();
        return $data;
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
        return User::find()->select('id')->where(['rights'=> 10])->orwhere(['rights'=> 0])->andWhere(['<','lastaccess',$old])->all();
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
            ->andWhere('imas_users.lastaccess > :date');
        $command = $query->createCommand();
        $data = $command->bindValue(':date',$date)->queryAll();
        return count($data);
    }

    public static function getStuCount($skipCid, $date, $skipCidS)
    {
        $query = new Query();
        $query->select('imas_students.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere('imas_users.lastaccess > :date');
        if (count($skipCid) > AppConstant::NUMERIC_ZERO) {
            $query->andWhere(['NOT IN', 'imas_students.courseid', $skipCidS]);
        }
        $command = $query->createCommand();
        $data = $command->bindValue(':date',$date)->queryAll();
        return count($data);
    }

    public static function queryForStu($skipCid, $date, $skipCidS)
    {
        $query = new Query();
        $query->select('imas_users.id')
            ->distinct('imas_users.id')
            ->from(['imas_users', 'imas_students'])
            ->where('imas_users.id=imas_students.userid')
            ->andWhere('imas_users.lastaccess >:date');
        if (count($skipCid) > AppConstant::NUMERIC_ZERO) {
            $query->andWhere(['NOT IN', 'imas_students.courseid', $skipCidS]);
        }
        $command = $query->createCommand();
        $data = $command->bindValue(':date',$date)->queryAll();
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
        $data = User::find()->select('email')->where(['>', 'rights', 20])->all();
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
        return $user;
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
        return $this;

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
        $user = User::getById($id);
        if ($myRights < AppConstant::ADMIN_RIGHT) {
            $user = User::find()->where(['id' => $id])->andWhere(['groupid' => $groupid])->andWhere(['<', 'rights', 100])->one();
        }
        $user->password = $md5pw;
        $user->save();
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
        return User::find()->select(['FirstName', 'LastName', 'email', 'id'])->where(['IN', 'id', $toList])->all();
    }

    public static function getByUserRight($myRight, $groupId)
    {
        $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19";

        if ($myRight < AppConstant::ADMIN_RIGHT) {
            $query .= " AND groupid='$groupId'";
        }
        $query .= " ORDER BY LastName";
       return Yii::$app->db->createCommand($query)->queryAll();
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
        return User::find()->select(['FirstName', 'SID', 'email'])->where(['id' => $id])->one();
    }

    public static function findPendingUser($offset)
    {
        return User::find()->select(['id', 'SID', 'LastName', 'FirstName', 'email'])->where(['=', 'rights', '0'])
            ->orWhere(['=', 'rights', '12'])->limit(AppConstant::NUMERIC_ONE)->offset($offset)->one();
    }

    public static function getUserGreaterThenTeacherRights()
    {
        return User::find()->select('id, FirstName, LastName')->where(['>=', 'rights', AppConstant::TEACHER_RIGHT])->orderBy('LastName,FirstName')->all();
    }

    public static function getByUserIdASDiagnoId($params)
    {
        $diagnoId = $params['id'];
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.groupid'])
            ->from('imas_users')
            ->join('JOIN',
                'imas_diags',
                'imas_users.id=imas_diags.ownerid'
            )
            ->andWhere('imas_diags.id= :diagnoId');
        $command = $query->createCommand()->bindValue('diagnoId', $diagnoId);
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
        return User::find()->select(['FirstName', 'LastName'])->where(['id' => $id])->one();
    }

    public static function getPwdUNameById($id)
    {
        return User::find()->select('password,LastName,FirstName')->where(['id' => $id])->all();
    }

    public static function getPasswordFromLtiUser($sid)
    {
        return User::find()->select('password')->where(['SID' => $sid])->andWhere(['rights' => 11])->andWhere(['rights' => 76])->andWhere(['rights' => 77])->one();
    }

    public static function getDataByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['iu.id','iu.LastName','iu.FirstName'])
            ->from('imas_users AS iu')
            ->join('JOIN',
                'imas_students AS istu',
                'iu.id = istu.userid'
            )
            ->where('istu.courseid = :courseId');
        $query->orderBy('iu.LastName,iu.FirstName');
        $command = $query->createCommand()->bindValue(':courseId',$courseId);
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteAdmin($groupId, $id)
    {
        $users = User::find()->where(['id' => $id])->andWhere(['groupid' => $groupId])->andWhere(['<', 'rights', 100])->all();
        foreach ($users as $user) {
            $user->delete();
        }
    }

    public static function getUserNameUsingStuGroup($stuGroupId)
    {
        $query = new query();
        $query->select('iu.FirstName,iu.LastName')->from('imas_users')->join('INNER JOIN','imas_stugroupmembers AS isgm','iu.id=isgm.userid')
            ->where('isgm.stugroupid = :stuGroupId')->orderBy('isgm.id')->limit('1');
        $command = $query->createCommand()->bindValue(':stuGroupId',$stuGroupId);
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataForExternalTool($UserId,$courseId,$isTutor,$tutorSection,$hasSection,$sortOrder)
    {
            $query  = new Query();
        $query->select('imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked')
            ->from('imas_users')
            ->join('INNER JOIN','imas_students','imas_users.id=imas_students.userid');
        if ($UserId!='all') {
            $query->where('imas_students.courseid=:courseid',['courseid' => $courseId])->andWhere('imas_users.id =:usersId',[':usersId' => $UserId]);
        } else {
            $query->where('imas_students.courseid=:courseid',['courseid' => $courseId]);
        }
        if ($isTutor && isset($tutorsection) && $tutorsection!='') {
            $query->andWhere('imas_students.section = :section',[':section' => $tutorsection]);
        }
        if ($hasSection && $sortOrder=="sec") {
            $query->orderBy('imas_students.section,imas_users.LastName,imas_users.FirstName');
        } else {
            $query->orderBy('imas_users.LastName,imas_users.FirstName');
        }
        $data = $query->createCommand()->queryAll();
        return $data;
    }

    public static function userDataUsingForum($userId,$forumId)
    {
        $data = User::find()->select(['iu.LastName','iu.FirstName','i_f.name','i_f.points','i_f.tutoredit','i_f.enddate'])
            ->from(['imas_users AS iu','imas_forums as i_f'])
            ->where(['iu.id' => $userId])
            ->andWhere(['i_f.id' => $forumId])->one();
        return $data;
    }
     public static function getDataById($id)
     {
         return User::find()->select(['FirstName','LastName','rights','groupid'])->where(['id' => $id])->one();
     }

    public static function getBySIDForAdmin($adminName)
    {
        return User::find()->select('id')->where(['SID' => $adminName])->one();
    }
    public function addUserFromAdmin($adminName, $passwd, $firstname, $lastname,$ten, $eclass, $groupId,$homelayout)
    {
        $this->SID = $adminName;
        $this->password = $passwd;
        $this->FirstName = $firstname;
        $this->LastName = $lastname;
        $this->rights = $ten;
        $this->email = $eclass;
        $this->groupid = $groupId;
        $this->homelayout = $homelayout;
        $this->save();
        return $this->id;
    }

    public static function updateUserRight($myRights, $newRights, $group, $id, $groupId)
    {
        if ($myRights < 100) {
            $user = User::find()->where(['id' => $id])->andWhere(['groupid' => $groupId])->andWhere(['<', 'rights', 100])->one();
        }
        else{
            $user = User::getById($id);
        }
        $user->rights = $newRights;
        if ($myRights == 100) {
            $user->groupid = $group;
        }
       $user->save();
    }

    public static function updateUserDetails($userId, $firstName, $lastName, $email, $msgNot, $qrightsdef, $deflib, $usedeflib, $layoutstr, $perpage,$chguserimg)
    {
        $user = static::findOne(['id' => $userId]);
        if($user)
        {
            $user->FirstName = $firstName;
            $user->LastName = $lastName;
            $user->email = $email;
            $user->msgnotify = $msgNot;
            $user->qrightsdef = $qrightsdef;
            $user->deflib = $deflib;
            $user->usedeflib = $usedeflib;
            $user->homelayout = $layoutstr;
            $user->listperpage = $perpage;
            $user->hasuserimg = $chguserimg;
            $user->save();
        }
    }

    public static function getUserPassword($userId)
    {
        return User::find()->select('password')->where(['id' => $userId])->one();
    }

    public static function updateUserPassword($userId, $md5pw)
    {
        $user = static::findOne(['id' => $userId]);
        if($user)
        {
            $user->password = $md5pw;
            $user->save();
        }
    }

    public static function getUserHomeLayoutInfo($id)
    {
        return User::find()->select(['homelayout','hideonpostswidget'])->where(['id' => $id])->one();
    }

    public static function getUserHideOnPostInfo($id)
    {
        return User::find()->select('hideonpostswidget')->where(['id' => $id])->one();
    }

    public static function updateHideOnPost($userId, $hideList)
    {
        $user = static::findOne(['id' => $userId]);
        if($user)
        {
            $user->hideonpostswidget = $hideList;
            $user->save();
        }
    }

    public static function lastViewsUser($thread)
    {
        $query = new Query();
        $query->select('iu.LastName,iu.FirstName,ifv.lastview')->from('imas_users AS iu')
            ->join('INNER JOIN','imas_forum_views AS ifv','iu.id=ifv.userid')->where('ifv.threadid = :thread')
            ->orderBy('ifv.lastview');
        $command = $query->createCommand()->bindValue(':thread',$thread);
        $data = $command->queryAll();
        return $data;
    }

    public function getQuestionRights($userId)
    {
        return self::find()->select('qrightsdef')->where(['id' => $userId])->one();
    }
    public function getFirstLastName($userId)
    {
        return self::find()->select('LastName,FirstName')->where(['id' => $userId])->one();
    }

    public function getMsgEmail($id){
        return self::find()->select('msgnotify,email')->where(['id' => $id])->one();
    }

    public function getUserStudentData($to,$fetchtCourseId){
        $query = new Query();
        $query->select('iu.LastName,iu.FirstName,iu.email,i_s.lastaccess,iu.hasuserimg')->from('imas_users AS iu')
            ->join('LEFT JOIN','imas_students AS i_s','iu.id=i_s.userid')->where('i_s.courseid=:fetchtCourseId')->andWhere('iu.id = :to');
        $command = $query->createCommand()->bindValues([':to'=>$to, ':fetchtCourseId' => $fetchtCourseId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getUserTeacherData($courseId)
    {
        $query = new Query();
        $query->select('imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid')
            ->from('imas_users')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_teachers.userid=imas_users.id'
            )->where(['imas_teachers.courseid' => $courseId]);
        $query->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function createInstructorAcc($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this;
    }

    public static function duplicateUserName($userName)
    {
        return User::find()->select('id')->where(['SID' => $userName])->all();
    }
}