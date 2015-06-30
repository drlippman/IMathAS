<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 5:54 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasStudents;
class Student extends BaseImasStudents {

    public function create($param)
    {
        $this->attributes = $param;
        $this->save();
    }
    public static function getByCourseId($courseId, $userId)
    {
        return static::findOne(['courseid' => $courseId, 'userid' => $userId]);
    }

    public static function getByUserId($id)
    {
        return static::findAll(['userid' => $id]);
    }

    public static function getByCId($cId)
    {
        return static::findOne(['courseid' => $cId]);
    }
    public static function getByUserIdentity($uid,$courseid)
    {

        return static::findAll(['userid' => $uid,'courseid' => $courseid]);
    }
    public function createNewStudent($userId,$cid,$param){

        $this->userid = $userId;
        $this->courseid = $cid;
        $this->section = empty($param['section']) ? null : $param['section'];
        $this->code = empty($param['code']) ? null : $param['code'];;
        $this->save();
    }
    public static function findByCid($cId){
        return static::findAll(['courseid'=>$cId]);
    }
    public function insertNewStudent($studentId,$courseId,$section)
    {
        $this->userid = $studentId;
        $this->courseid = $courseId;
        $this->section = empty($section) ? null : $section;
        $this->save();
    }
    public static function updateSectionAndCodeValue($section, $userid, $code, $cid,$params = null)
    {
        $student = Student::findOne(['userid' => $userid, 'courseid' => $cid]);
        $student->section = $section;
        $student->code = $code;
        if($params != null)
        {
           if($params['locked'] == 1) {
               $student->locked = strtotime(date('F d, o g:i a'));
           }
            else{
                $student->locked = 0;
            }
           $student->hidefromcourselist = $params['hidefromcourselist'];

            if($params['timelimitmult'] != 0)
            {
                $student->timelimitmult =  $params['timelimitmult'];
            }
        }
        $student->save();
    }
    public static function updateLatepasses($latepass,$userid,$cid)
    {
        $student = Student::findOne(['userid' => $userid, 'courseid' => $cid]);
        $student->latepass = $latepass;
        $student->save();
    }
    public static function findByCourseId($cId,$sortBy, $order){
        return static::find()->where(['courseid'=>$cId])->groupBy('section')->orderBy([$sortBy => $order])->all();
    }
    public static function updateLocked($userid,$courseid)
    {
        $student = Student::findOne(['userid' => $userid,'courseid' => $courseid]);
        $student->locked = strtotime(date('F d, o g:i a'));
        $student->save();
    }
    public static function deleteStudent($userid,$courseid)
    {
        $student = Student::findOne(['userid' => $userid,'courseid' => $courseid]);
        $student->delete();
    }
    public function assignSectionAndCode($newEntry,$id)
    {
        $this->userid = $id;
        $this->section = $newEntry['5'];
        $this->code = $newEntry['4'];
        $this->save();
    }
    public static function updateLockOrUnlockStudent($params)
    {
        $courseId = $params['courseId'];
        $studentId = $params['studentId'];
        $student = Student::findOne(['userid' => $studentId,'courseid' => $courseId]);
        if($params['lockOrUnlock'] == 1){
            $student->locked = 0;
            $student->save();
         }
        if($params['lockOrUnlock'] == 0)
        {
            $student->locked = strtotime(date('F d, o g:i a'));
            $student->save();
        }
    }
    public static function reduceLatepasses($userid, $cid, $n)
    {
        $student = Student::findOne(['userid' => $userid, 'courseid' => $cid]);
        if($student->latepass > $n){
            $student->latepass = $student->latepass - $n;
        }
        else{
            $student->latepass = 0;
        }
        $student->save();
    }
    public static function updateHideFromCourseList($userId, $courseId)
    {
        $student = Student::findOne(['userid' => $userId, 'courseid' => $courseId]);
        if($student){
            if($student->hidefromcourselist == 0){
                $student->hidefromcourselist = 1;
            }else{
                $student->hidefromcourselist = 0;
            }
            $student->save();
        }
    }
    public static function findHiddenCourse($userId)
    {
        return static::find()->where(['userid'=>$userId])->andWhere(['NOT LIKE', 'hidefromcourselist', 0 ])->all();
    }

    public static function findDistinctSection($courseId)
    {
        return static::find()->select('section')->distinct()->where(['courseid' => $courseId])->all();
    }

} 