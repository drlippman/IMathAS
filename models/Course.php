<?php
namespace app\models;


use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasCourses;
use Yii;
use yii\db\Exception;
use yii\db\Query;

class Course extends BaseImasCourses {

    public function create($user, $bodyParams)
    {
        $params = $bodyParams['CourseSettingForm'];
        $course['ownerid'] = $user->id;
        $course['name'] = $params['courseName'];
        $course['enrollkey'] = $params['enrollmentKey'];
        $availables = isset($params['available']) ? $params['available'] : AppConstant::AVAILABLE_NOT_CHECKED_VALUE;
        $course['available'] = AppUtility::makeAvailable($availables);
        $course['picicons'] = AppConstant::PIC_ICONS_VALUE;
        $course['allowunenroll'] = AppConstant::UNENROLL_VALUE;
        $course['copyrights'] = $params['copyCourse'];
        $course['msgset'] = $params['messageSystem'];
        $toolsets = isset($params['navigationLink']) ? $params['navigationLink'] : AppConstant::NAVIGATION_NOT_CHECKED_VALUE;
        $isTemplate = isset($params['courseAsTemplate']) ? $params['courseAsTemplate'] : AppConstant::NUMERIC_ZERO;
        $course['istemplate'] = AppUtility::createIsTemplate($isTemplate);
        $course['toolset']  = AppUtility::makeToolset($toolsets);
        $course['cploc']= AppConstant::CPLOC_VALUE;
        $course['deflatepass']= $params['latePasses'];
        $course['showlatepass']= AppConstant::SHOWLATEPASS;
        $course['theme']= $params['theme'];
        $course['deftime'] = AppUtility::calculateTimeDefference($bodyParams['start_time'],$bodyParams['end_time']);
        $course['end_time'] = $bodyParams['end_time'];
        $course['chatset'] = AppConstant::CHATSET_VALUE;
        $course['topbar'] = AppConstant::TOPBAR_VALUE;
        $course['hideicons'] = AppConstant::HIDE_ICONS_VALUE;
        $course['itemorder'] = AppConstant::ITEM_ORDER;

        $course = AppUtility::removeEmptyAttributes($course);
        $this->attributes = $course;
        $this->save();


        return $this->id;
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
        return static::findOne(['id' => $cid]);
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
        return Course::find()->select('outcomes')->where(['id' => $courseId])->all();
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

    public static function setOwner($params,$user){
        if($user->rights < AppConstant::GROUP_ADMIN_RIGHT){
            $courseData = Course::findAll(['id' => $params['cid'],'ownerid' => $user->id]);
        }else{
            $courseData = Course::findOne(['id' => $params['cid']]);
        }
        if($courseData){
            $courseData->ownerid = $params['newOwner'];
            $courseData->save();
            return $courseData->id;
        }
    }

    public static function getByCourseAndGroupId($params,$user)
    {
        return Yii::$app->db->createCommand("SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$params['cid']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='{$user['groupid']}'")->queryAll();
    }

    public static function getByAvailable($params){
        if(isset($params['cid'])){
            $courseId = intval($params['cid']);
            return Yii::$app->db->createCommand("SELECT id FROM imas_courses WHERE (istemplate&8)=8 AND available<4 AND id= $courseId")->queryAll();
        }else{
            return Yii::$app->db->createCommand("SELECT id FROM imas_courses WHERE (istemplate&8)=8 AND available<4")->queryAll();
        }
    }

    public static function getByCourseAndUser($cid){
        return Yii::$app->db->createCommand("SELECT imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.topbar,imas_courses.toolset,imas_courses.deftime,imas_courses.picicons FROM imas_courses,imas_users WHERE imas_courses.id= $cid AND imas_users.id=imas_courses.ownerid")->queryAll();
    }
    public static function updateNewFlag($courseId)
    {
        $course = Course::find()->where(['id' => $courseId])->one();
        $newflag = $course['newflag'];
        $newflag = $newflag ^ AppConstant::NUMERIC_ONE;
        $course->newflag = $newflag;
        $course->save();
    }
}

