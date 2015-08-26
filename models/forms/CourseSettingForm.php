<?php

namespace app\models\forms;

use app\components\AppUtility;
use app\models\_base\BaseImasCourses;
use Yii;
use yii\base\Model;

class CourseSettingForm extends Model
{
    public $courseId;
    public $courseName;
    public $enrollmentKey;
    public $available;
    public $time;
    public $theme;
    public $icons;
    public $showIcons;
    public $selfUnenroll;
    public $selfEnroll;
    public $copyCourse;
    public $messageSystem;
    public $navigationLink;
    public $courseReordering;
    public $latePasses;
    public $remainingLatePasses;
    public $studentQuickPick;
    public $instructorQuickPick;
    public $quickPickBar;
    public $courseManagement;
    public $viewControl;
    public $studentLink;
    public $courseAsTemplate;
    public $AvailableToStudents;
    public $ShowOnInstructorsHomePage;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['courseName', 'enrollmentKey'], 'required'],
            [['courseName'], 'string', 'max' => 30],
            [['enrollmentKey'], 'string', 'max' => 20],
            ['latePasses', 'number']
        ];
    }

    public function attributeLabels()
    {
        return [
    'courseId' => AppUtility::t('Course Id', false),
    'courseName'=> AppUtility::t('Enter Course name', false),
    'enrollmentKey'=> AppUtility::t('Enter Enrollment key', false),
    'available'=> AppUtility::t('Available?', false),
    'time'=> AppUtility::t('Default start/end time for new items', false),
    'theme'=> AppUtility::t('Theme', false),
    'icons'=> AppUtility::t('Icons', false),
    'showIcons'=> AppUtility::t('Show Icons', false),
    'selfUnenroll'=> AppUtility::t('Allow students to self-unenroll', false),
    'selfEnroll'=> AppUtility::t('Allow students to self-enroll', false),
    'copycourse'=> AppUtility::t('Allow other instructors to copy course items', false),
    'messageSystem'=> AppUtility::t('Message System', false),
    'navigationLink'=> AppUtility::t('Navigation Links for Students', false),
    'courseReordering'=> AppUtility::t('Pull-downs for course item reordering', false),
    'latePasses'=> AppUtility::t('Auto-assign LatePasses on course enroll', false),
    'remainingLatePasses'=> AppUtility::t('Show remaining LatePasses on student gradebook page', false),
    'studentQuickPick'=> AppUtility::t('Student Quick Pick Top Bar items', false),
    'instructorQuickPick'=> AppUtility::t('Instructor Quick Pick Top Bar items', false),
    'quickPickBar'=> AppUtility::t('Quick Pick Bar location', false),
    'courseManagement'=> AppUtility::t('Instructor course management links location', false),
    'viewControl'=> AppUtility::t('View Control links', false),
    'studentLink'=> AppUtility::t('Student links location', false),
    'courseAsTemplate'=> AppUtility::t('Mark course as template?', false),
        ];
    }
    public static function findCourseData($sortBy, $order)
    {
        return BaseImasCourses::find()->orderBy([$sortBy => $order])->all();
    }

}
