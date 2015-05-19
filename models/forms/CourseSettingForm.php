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
            ['latePasses', 'number']
        ];
    }

    public function attributeLabels()
    {
        return [
    'courseName'=>'Enter Course name',
    'enrollmentKey'=>' Enter Enrollment key',
    'available'=>'Available?',
    'time'=>'Default start/end time for new items',
    'theme'=>'Theme',
    'icons'=>'Icons',
    'showIcons'=>'Show Icons',
    'selfUnenroll'=>'Allow students to self-unenroll',
    'selfEnroll'=>'Allow students to self-enroll',
    'copyCourse'=>'Allow other instructors to copy course items',
    'messageSystem'=>'Message System',
    'navigationLink'=>'Navigation Links for Students',
    'courseReordering'=>'Pull-downs for course item reordering',
    'latePasses'=>'Auto-assign LatePasses on course enroll',
    'remainingLatePasses'=>'Show remaining LatePasses on student gradebook page',
    'studentQuickPick'=>'Student Quick Pick Top Bar items',
    'instructorQuickPick'=>'Instructor Quick Pick Top Bar items',
    'quickPickBar'=>'Quick Pick Bar location',
    'courseManagement'=>'Instructor course management links location',
    'viewControl'=>'View Control links',
    'studentLink'=>'Student links location',
    'courseAsTemplate'=>'Mark course as template?',
        ];
    }
    public static function findCourseData($sortBy, $order)
    {
        return BaseImasCourses::find()->orderBy([$sortBy => $order])->all();
    }

}
