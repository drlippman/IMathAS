<?php
namespace app\models;
use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasCourses;
use Yii;
use yii\db\Exception;
use yii\db\Query;

class Course extends BaseImasCourses {

    public function create($user, $params, $blockcnt)
    {
        $course['ownerid'] = $user;
        $course['name'] = $params['coursename'];
        $course['enrollkey'] = $params['ekey'];
        $availables = isset($params['avail']) ? $params['avail'] : AppConstant::AVAILABLE_NOT_CHECKED_VALUE;
        $course['available'] = $params['avail'];
        $course['picicons'] = AppConstant::PIC_ICONS_VALUE;
        $course['allowunenroll'] = AppConstant::UNENROLL_VALUE;
        $course['copyrights'] = $params['copyrights'];
        $course['msgset'] = $params['msgset'];
        $toolsets = isset($params['toolset']) ? $params['toolset'] : AppConstant::NAVIGATION_NOT_CHECKED_VALUE;
        $isTemplate = isset($params['istemplate']) ? $params['istemplate'] : AppConstant::NUMERIC_ZERO;
        $course['istemplate'] = AppUtility::createIsTemplate($isTemplate);
        $course['toolset']  = AppUtility::makeToolset($toolsets);
        $course['cploc']= AppConstant::CPLOC_VALUE;
        $course['deflatepass']= $params['deflatepass'];
        $course['showlatepass']= AppConstant::SHOWLATEPASS;
        $course['theme']= AppConstant::DEFAULT_THEME;
        $course['deftime'] = AppUtility::calculateTimeDefference($params['defstime'],$params['deftime']);
        $course['chatset'] = AppConstant::CHATSET_VALUE;
        $course['topbar'] = AppConstant::TOPBAR_VALUE;
        $course['hideicons'] = AppConstant::HIDE_ICONS_VALUE;
        $course['itemorder'] = AppConstant::ITEM_ORDER;
        $course['blockcnt'] = $blockcnt;
        $data = AppUtility::removeEmptyAttributes($course);
        $this->attributes = $data;
        $this->save();
        return $this;
    }

    public static function getByIdAndEnrollmentKey($id, $enroll)
    {
       return static::findOne(['id' =>$id, 'enrollkey' => $enroll]);
    }

    public static function getByCourseName($name)
    {
        return static::findAll(['name' => $name]);
    }

    public static function getById($cid)
    {
        return Course::findOne(['id' =>$cid]);
    }
    public static function getByIdandOwnerId($id, $ownerId)
    {
        return static::findOne(['id' =>$id, 'ownerid' => $ownerId]);
    }

    public static function deleteCourse($cid)
    {
        $connection = Yii::$app->getDb();
        $transaction = $connection->beginTransaction();
        try {
            $connection->createCommand()->delete('imas_courses', 'id ='.$cid)->execute();
            $connection->createCommand()->delete('imas_assessments', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_badgesettings', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_calitems', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_content_track', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_diags', 'cid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_external_tools', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_drillassess', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_firstscores', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_forums', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_gbcats', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_gbitems', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_gbscheme', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_inlinetext', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_items', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_linkedtext', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_login_log', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_lti_courses', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_msgs', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_outcomes', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_students', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_stugroupset', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_teachers', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_tutors', 'courseid ='.$cid)->execute();
            $connection->createCommand()->delete('imas_wikis', 'courseid ='.$cid)->execute();
            $transaction->commit();
            return true;

        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    public static function findCourseDataArray()
    {
        $query = new Query();
        $query	->select(['imas_users.id as userid','imas_users.FirstName', 'imas_users.LastName', 'imas_courses.name', 'imas_courses.id as courseid'])
            ->from('imas_courses')
            ->join(	'LEFT OUTER JOIN',
                'imas_users',
                'imas_users.id = imas_courses.ownerid'
            );
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function findByName($name){
        return static::findOne(['name'=>$name]);
    }
    public static function updatePassHours($latepasshours,$cid)
    {
        $student = Course::findOne(['id' => $cid]);
        $student->latepasshrs = $latepasshours;
        $student->save();
    }

    public static function setItemOrder($itemList, $courseId)
    {
        $course = Course::findOne(['id' => $courseId]);
        $course->itemorder = $itemList;
        $course->save();
    }

    public static function getItemOrder($courseId)
    {
        return Course::findOne(['id' => $courseId]);
    }

    public static function getOutcome($courseId){
        return Course::find()->select('outcomes')->where(['id' => $courseId])->one();
    }

    public function SaveOutcomes($courseId,$outcomeGrp)
    {

        $isRecord = Course::findAll(['id' =>$courseId]);
        if($isRecord)
        {
               foreach($isRecord as $outcome)
               {
                   $outcome->outcomes = $outcomeGrp;
                   $outcome->save();
               }
        }
    }

    public static function getByCourseIdOutcomes($courseId)
    {
        return Course::find()->select('outcomes')->where(['id' => $courseId])->all();
    }

    public static function setBlockCount($itemOrder,$blockCount,$courseId)
    {
        $course = Course::findOne(['id' => $courseId]);
        $course->itemorder = $itemOrder;
        $course->blockcnt = $blockCount;
        $course->save();
    }
    public static function getByCourseAndUser($cid)
    {
        $query = new Query();
        $query->select('imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.topbar,imas_courses.toolset,imas_courses.deftime,imas_courses.picicons')
            ->from('imas_courses')
            ->join('INNER JOIN',
                'imas_users',
                'imas_users.id = imas_courses.ownerid');
        $query->where(['imas_courses.id' => $cid]);
        return $query->createCommand()->queryOne();
    }
    public static function UpdateItemOrder($finalBlockItems,$course,$blockCnt)
    {
        $isRecord = Course::findOne(['id' =>$course]);
        if($isRecord)
        {
            $isRecord->itemorder = $finalBlockItems;
            $isRecord->blockcnt = $blockCnt;
            $isRecord->save();
        }
    }

    public static function getByCourseAndGroupId($courseId,$userId)
    {
        return self::find()->select(['imas_courses.id'])->where(['imas_courses.id'=> $courseId])->andWhere(['imas_courses.ownerid=imas_users.id'])->andWhere(['imas_users.groupid' => $userId])->all();
    }

    public static function getByAvailable($params)
    {
        $query = new Query();
        if(isset($params['cid']))
        {
            $courseId = intval($params['cid']);
            $data = $query->select('id')->from('imas_courses')->where(['istemplate' & 8 => 8])->andWhere(['<','available',4])->andWhere('id = :courseId');
            return $data->createCommand()->bindValue(':courseId', $courseId)->queryAll();
        }else{
            $query->select('id')->from('imas_courses')->where(['istemplate' & 8 => 8])->andWhere(['<','available',4])->createCommand()->queryAll();
        }
    }

    public static function setOwner($params,$user){
        if($user->rights < AppConstant::GROUP_ADMIN_RIGHT){
            $courseData = Course::findOne(['id' => $params['cid'],'ownerid' => $user->id]);
        }else{
            $courseData = Course::findOne(['id' => $params['cid']]);
        }
        if($courseData){
            $courseData->ownerid = $params['newOwner'];
            $courseData->save();
            return $courseData->id;
        }
    }

    public static function updateNewFlag($courseId)
    {
        $course = Course::find()->where(['id' => $courseId])->one();
        $newflag = $course['newflag'];
        $newflag = $newflag ^ AppConstant::NUMERIC_ONE;
        $course->newflag = $newflag;
        $course->save();
    }
 /*
  *Query To Show Courses available For Teacher in My classes drop-down
  */
    public static  function getGetMyClasses($userId)
    {
        $items = [];
        $myClasses = Course::find()->where(['ownerid' => $userId])->all();
        foreach($myClasses as $key => $singleClass)
        {
            $items[] = ['label' => $singleClass->name, 'url' => '../../course/course/course?cid='.$singleClass['id']];
            if(count($myClasses) == $key+AppConstant::NUMERIC_ONE)
            {
                array_push($items,'<li class="divider"></li>');
                array_push($items,['label' => 'Manage Questions', 'url' => '#']);
                array_push($items,['label' => 'Questions Libraries', 'url' => '#']);
            }
        }
        return $items;
    }

    public static function getCidForCopyingCourse($userId,$ctc)
    {
        $query = new Query();
        $query->select(['imas_courses.id'])
            ->from('imas_courses')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_courses.id=imas_teachers.courseid'
            )
            ->where('imas_teachers.userid= :userid');
        $query->andWhere('imas_courses.id= :ctc');
        $command = $query->createCommand()->bindValues(['userid'=> $userId, 'ctc' => $ctc]);
        $items = $command->queryOne();
        return $items;
    }

    public static function getEnrollKey($ctc)
    {
        $query = new Query();
        $query	->select(['enrollkey','copyrights'])
            ->from('imas_courses')
            ->where('id= :id',[':id' => $ctc]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;

    }
    public static function getDataForCopyCourse($ctc)
    {
        $query = new Query();
        $query->select('imas_users.groupid')->from('imas_courses,imas_users,imas_teachers')
            ->where('imas_courses.id=imas_teachers.courseid')->andWhere('imas_teachers.userid=imas_users.id')
            ->andWhere('imas_courses.id= :ctc');
        return $query->createCommand()->bindValue('ctc',$ctc)->queryOne();
    }

    public static function getDataByCtc($toCopy,$ctc)
    {
        return self::find()->select($toCopy)->where(['id' => $ctc])->one();
    }

    public static function updateCourseForCopyCourse($courseId,$sets)
    {
        $course = self::find()->where(['id' => $courseId])->one();
        $course->attributes = $sets;
        $course->save();
    }
    public static function updateOutcomes($newOutcomeArr,$courseId)
    {
        $outcomes = Course::find()->where(['id' => $courseId])->one();
        if($outcomes)
        {
            $outcomes->outcomes = $newOutcomeArr;
            $outcomes->save();
        }

    }
    public static function getBlockCnt($courseId)
    {
        return self::find()->select('blockcnt')->where('id', $courseId)->one();
    }

    public static function getCourseData($myRights, $showcourses, $userId)
    {

        $query = new Query();
        $query	->select(['imas_courses.id','imas_courses.ownerid','imas_courses.name','imas_courses.available','imas_users.FirstName','imas_users.LastName'])
            ->from('imas_courses')
            ->join('JOIN',
                'imas_users',
                'imas_courses.ownerid=imas_users.id'
            );
        if($myRights > AppConstant::ADMIN_RIGHT)
        {
            $query->andWhere(['<','imas_courses.available',4]);
        }
        (($myRights >= AppConstant::LIMITED_COURSE_CREATOR_RIGHT && $myRights < AppConstant::GROUP_ADMIN_RIGHT) || $showcourses==AppConstant::NUMERIC_ZERO) ? $query->andWhere('imas_courses.ownerid = :userId') : $query->andWhere(':userId = :userId');
        if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT && $showcourses > AppConstant::NUMERIC_ZERO)
        {
            $query->andWhere('imas_courses.ownerid = :showcourses');
            $query->orderBy('imas_users.LastName,imas_courses.name');
        } else{
            $query->andWhere(':showcourses = :showcourses');
            $query->orderBy('imas_courses.name');
        }
        $command = $query->createCommand()->bindValues([':userId' => $userId,':showcourses' => $showcourses]);
        $data = $command->queryAll();
        return $data;
     }

    public static function getPicIcons($ctc)
    {
        return self::find()->select(['itemorder','picicons'])->where(['id' => $ctc])->one();
    }
    public static function getDataByJoins($groupId,$userId)
    {
        return self::find()->select('ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid')
            ->from('imas_courses AS ic,imas_teachers AS it,imas_users AS iu,imas_groups')->where('it.courseid=ic.id')
            ->andWhere('it.userid=iu.id')->andWhere('iu.groupid=imas_groups.id')->andWhere('<>','iu.groupid=:groupId',[':groupId' => $groupId])
            ->andWhere('<>','iu.id=:userId',[':userId' => $userId])
            ->andWhere(['<','ic.available',4])->orderBy('imas_groups.name,iu.LastName,iu.FirstName,ic.name')->all();
    }
    public static function getFromJoinOnTeacher($userId,$courseId)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_teachers.courseid=ic.id'
            )
            ->where('imas_teachers.userid = :userId');
        $query->andWhere( 'ic.id <> :courseId');
        $query->andWhere(['<','ic.available','4'])->orderBy('ic.name');
        $command = $query->createCommand()->bindValues(['userId' => $userId,'courseId' => $courseId]);
        $items = $command->queryAll();
        return $items;
    }
    public static function getFromJoinsData($groupId,$userId)
    {
        $query = new Query();
        $query->select('ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid')->from('imas_courses AS ic,imas_teachers AS it,imas_users AS iu')
            ->where('it.courseid=ic.id')->andWhere('it.userid=iu.id')->andWhere('iu.groupid= :groupId')->andWhere('iu.id <> :userId')-> andWhere('ic.available < 4')
            ->orderBy('iu.LastName,iu.FirstName,ic.name');
        $command = $query->createCommand()->bindValues(['groupId' => $groupId, 'userId' => $userId]);
        return $command->queryAll();
    }

    public static function getTemplate()
    {
        return self::find()->select('id,name,copyrights')->where('(istemplate&1)=1')->andWhere('copyrights=2')->andWhere('available<4')->orderBy('name')->all();
    }
    public static function getGroupTemplate($groupId)
    {
        $query = new Query();
        $query->select('ic.id,ic.name,ic.copyrights')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN',
                'imas_users AS iu',
                'ic.ownerid=iu.id'
            )
            ->where('iu.groupid = :groupId');
        $query->andWhere(['(ic.istemplate&2)' => 2]);
        $query->andWhere(['>','ic.copyrights','0']);
            $query->andWhere(['<','ic.available','4'])->orderBy('ic.name');
        $command = $query->createCommand()->bindValue('groupId',$groupId);
        $items = $command->queryAll();
        return $items;
    }

    public static function queryForCourse($id)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN',
                'imas_students AS istu',
                'istu.courseid=ic.id'
            )
            ->where('istu.userid= :id');
        $command = $query->createCommand()->bindValue('id',$id);
        $items = $command->queryAll();
        return $items;
    }
    public static function queryFromCourseForTutor($id)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN',
                'imas_tutors AS istu',
                'istu.courseid=ic.id'
            )
            ->where('istu.userid=:id');
        $command = $query->createCommand()->bindValue('id',$id);
        $items = $command->queryAll();
        return $items;
    }
    public static function queryFromCourseForTeacher($id)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN',
                'imas_teachers AS istu',
                'istu.courseid=ic.id'
            )
            ->where('istu.userid= :id');
        $command = $query->createCommand()->bindValue('id',$id);
        $items = $command->queryAll();
        return $items;
    }
    public static function getItemOrderAndBlockCnt($ctc)
    {
        $query = new Query();
        $query	->select(['itemorder','blockcnt'])
            ->from('imas_courses')
            ->where('id= :id',[':id' => $ctc]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }
    public static function getByUserId($userId)
    {
        $query = new Query();
        $query->select('imas_courses.id,imas_courses.name')
            ->from('imas_courses')
            ->join('INNER JOIN',
                'imas_teachers',
                'imas_courses.id=imas_teachers.courseid'
            )
            ->where('imas_teachers.userid = :userId')->orderBy('imas_courses.name');
        $command = $query->createCommand()->bindValue('userId',$userId);
        $items = $command->queryAll();
        return $items;

    }
    public static function UpdateItemOrderAndBlockCnt($itemOrder,$blockCnt,$courseId,$num)
    {
        $isRecord = Course::findOne(['id' =>$courseId]);
        if($isRecord)
        {
            $isRecord->itemorder = $itemOrder;
            if($num == AppConstant::NUMERIC_ZERO)
            {
                $isRecord->blockcnt = $blockCnt+AppConstant::NUMERIC_ONE;
            }
            $isRecord->save();
        }
    }

    public static function getDataByTemplate()
    {
        $query = new Query();
        $query	->select(['id'])
            ->from('imas_courses')
            ->where('(istemplate&4)=4');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getBlckTitles($search)
    {
        $query = new Query();
        $query	->select(['id','itemorder','name'])
            ->from('imas_courses')
            ->where(['LIKE','itemorder',$search])->limit(AppConstant::LIMITED_COURSE_CREATOR_RIGHT);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getOutComeByCourseId($cid)
    {
        return self::find()->select(['outcomes'])->where(['id' => $cid])->one();
    }

    public function updateCourse($params, $available, $toolSet, $defTime,$columnName, $columnValue)
    {
        $updateCourse = Course::find()->where(['id' => $params['id']])->andWhere([$columnName => $columnValue])->one();
        if($updateCourse){
            $updateCourse->name = $params['coursename'];
            $updateCourse->enrollkey = $params['ekey'];
            $updateCourse->available = $available;
            $updateCourse->copyrights = $params['copyrights'];
            $updateCourse->msgset = $params['msgset'];
            $updateCourse->toolset = $toolSet;
            $updateCourse->ltisecret = $params['ltisecret'];
            $updateCourse->deftime = $defTime;
            $updateCourse->deflatepass = $params['deflatepass'];
            $updateCourse->lockaid = $params['lockaid'];
            $updateCourse->save();
            return $updateCourse;
        }
    }

    public static function setOwnerId($params, $columnName, $columnValue)
    {
        $updateOwnerId = Course::find()->where(['id' => $params['id']])->andWhere([$columnName => $columnValue])->one();
        if($updateOwnerId)
        {
            $updateOwnerId->ownerid = $params['newowner'];
            $updateOwnerId->save();
        }
    }
    public static function getCidAndUid($params, $groupId)
    {
        $query = new Query();
        $query	->select(['imas_courses.id'])
            ->from(['imas_courses', 'imas_users'])
            ->where('imas_courses.id=:courseId',[':courseId' => $params['id']]);
        $query->andWhere(['imas_courses.ownerid' => 'imas_users.id']);
        $query->andWhere('imas_users.groupid=:groupId',[':groupId' => $groupId]);
        $command = $query->createCommand();
        $data = $command->queryone();
        return $data;
    }

    public static function setOwnerIdByExecute($params)
    {
        $query = self::find()->where(['id' => $params['id']])->one();
        $query->ownerid = $params['newowner'];
        $query->save();
    }

    public static function getByIdandOwnerIdByAll($id, $ownerId)
    {
        return static::findAll(['id' => $id, 'ownerid' => $ownerId]);
    }

    public static function setAvailable($params)
    {
        $updateAvail = Course::find()->where(['id' => $params['id']])->one();
        if($updateAvail){
            $updateAvail->available = AppConstant::NUMERIC_FOUR;
            $updateAvail->save();
        }
    }
    public static function deleteByCourseId($params, $myRights, $userId)
    {
        $query = Course::find()->where(['id' => $params['id']])->one();
        if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $query = self::find()->where(['id' => $params['id']])->andWhere(['ownerid' => $userId])->one();
        }
        if($query)
        {
            $query->delete();
        }
    }

    public static function deleteById($params)
    {
        $deleteId = Course::find()->where(['id' => $params['id']])->one();
        if($deleteId)
        {
            $deleteId->delete();
        }
    }

    public static function getByLatePasshrs($courseId)
    {
        return self::find()->select(['latepasshrs'])->where(['id' => $courseId])->all();
    }

    public static function getLatePassHrs($courseId)
    {
        return Course::find()->select('latepasshrs')->where(['id' => $courseId])->one();
    }


    public static function getAllCourses()
    {
        $query = new Query();
        $query	->select('imas_courses.id,imas_courses.name,imas_users.LastName,imas_users.FirstName')
            ->from(['imas_courses', 'imas_users'])
            ->where('imas_users.id = imas_courses.ownerid');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function  getByName($id)
    {
        return Course::find()->select('*')->where(['ownerid' => $id])->orderBy('name')->all();
    }

    public static function getCourseOfStudent($userId)
    {
        $query = new Query();
        $query->select('imas_courses.name,imas_courses.id,imas_students.hidefromcourselist')->from('imas_students')
            ->join('INNER JOIN','imas_courses','imas_students.courseid=imas_courses.id')
            ->where('imas_courses.available=0')->orWhere('imas_courses.available=2')
            ->andWhere('imas_students.userid=:userId',[':userId'=> $userId])->orderBy('imas_courses.name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getCourseOfTeacher($userId)
    {
        $query = new Query();
        $query->select('imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid')->from('imas_teachers')->
        join('INNER JOIN','imas_courses','imas_teachers.courseid = imas_courses.id')
            ->where('imas_courses.available=0')->orWhere('imas_courses.available=1')
            ->andWhere('imas_teachers.userid = :userId')
            ->orderBy('imas_courses.name');
        $command = $query->createCommand()->bindValue('userId',$userId);
        $data = $command->queryAll();
        return $data;
    }

    public static function getCourseDataById($courseId)
    {
        return self::find()->select(['name','itemorder','hideicons','picicons','allowunenroll','msgset','toolset','chatset','topbar','cploc','latepasshrs'])->where(['id' => $courseId])->one();
    }

    public static function isOwner($userId,$courseId)
    {
        return self::find()->select('ownerid')->where(['id' => $courseId])->one();
    }

    public static function getEnrollData($courseId)
    {
        return self::find()->select('enrollkey,allowunenroll,deflatepass')->from('imas_courses')->where(['available' => '0'])->orWhere(['available' => 2])->andWhere(['id' => $courseId])->one();
    }

    public static function getDataPublicly($courseId)
    {
        return self::find()->select('name,itemorder,hideicons,picicons,allowunenroll,msgset,chatset,topbar,cploc')->where(['id' => $courseId])->one();
    }

    public static function getDataPubliclyForBlock($courseId)
    {
        return self::find()->select('name,itemorder,hideicons,picicons,allowunenroll,msgset,topbar,cploc')->where(['id' => $courseId])->one();
    }

    public static function getIdPublicly($courseId)
    {
        return self::find()->select('itemorder,name,theme')->where(['id' => $courseId])->one();
    }

    public function getNameById($id){
        return self::find()->select('name')->where(['id' => $id])->one();
    }

    public static function getMsgSet($id){
        return Course::find()->select('msgset')->where(['id' => $id])->one();
    }

    public static function getByOutcomes($courseId)
    {
        return Course::find()->select('outcomes')->where(['id' => $courseId])->one();
    }
}
