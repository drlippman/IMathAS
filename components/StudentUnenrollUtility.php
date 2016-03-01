<?php
namespace app\components;

use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\ContentTrack;
use app\models\Drillassess;
use app\models\DrillassessSessions;
use app\models\Exceptions;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\ForumView;
use app\models\GbItems;
use app\models\Grades;
use app\models\LinkedText;
use app\models\LoginLog;
use app\models\Questions;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\Wiki;
use app\models\WikiRevision;
use app\models\WikiView;
use Yii;
use yii\base\Component;

class StudentUnenrollUtility extends Component
{
    public static function unenrollStudent($params)
    {
        if ($params['uid'] == "selected") {
            $tounenroll = explode(',', $params['studentData']);
        } elseif ($params['uid'] == 'all') {
            $query = Student::getByCourse($params['cid']);
            foreach ($query as $student) {
                $tounenroll[] = $student['userid'];
            }
        } else {
            $tounenroll[] = $params['uid'];
        }
        if (!isset($params['delwikirev'])) {
            $delwikirev = intval($_POST['delwikirev']);
        } else {
            $delwikirev = AppConstant::NUMERIC_ZERO;
        }
        if (isset($params['removewithdrawn'])) {
            $withwithdraw = 'remove';
        } else if ($params['uid'] == "all") {
            $withwithdraw = 'unwithdraw';
        } else {
            $withwithdraw = false;
        }
        StudentUnenrollUtility::unenrollstu($params['cid'], $tounenroll, ($params['uid'] == "all" || isset($params['delforumposts'])), ($params['uid'] == "all" && isset($params['removeoffline'])), $withwithdraw, $delwikirev, isset($params['usereplaceby']));
    }

    public static function unenrollstu($cid, $tounenroll, $delforum = false, $deloffline = false, $withwithdraw = false, $delwikirev = false, $usereplaceby = false)
    {
        $forums = array();
        $threads = array();
        $query = Forums::getByCourseId($cid);
        foreach ($query as $forum) {
            $forums[] = $forum['id'];
            $query2 = ForumPosts::findByForumId($forum['id']);
            foreach ($query2 as $forumPost) {
                $threads[] = $forumPost['threadid'];
            }
        }
        $forumlist = implode(',', $forums);
        $assesses = array();
        $query = Assessments::getByCourseId($cid);
        foreach ($query as $assessments) {
            $assesses[] = $assessments['id'];
        }

        $wikis = array();
        $grpwikis = array();
        $query = Wiki::getByCourseId($cid);
        foreach ($query as $wiki) {
            $wikis[] = $wiki['id'];
            if ($wiki['groupsetid'] > AppConstant::NUMERIC_ZERO) {
                $grpwikis[] = $wiki['id'];
            }
        }

        $drills = array();
        $query = Drillassess::getByCourseId($cid);
        foreach ($query as $drillAssessmet) {
            $drills[] = $drillAssessmet['id'];
        }

        $exttools = array();
        $query = LinkedText::findByCourseId($cid);
        foreach ($query as $linkedText) {
            $exttools[] = $linkedText['id'];
        }

        $stugroups = array();
        $query = Stugroups::findByCourseId($cid);
        foreach ($query as $stuGroup) {
            $stugroups[] = $stuGroup['id'];
        }

        /*
         * File handler functionality is remaining (filehandler.php)
         */


        if (count($tounenroll) > AppConstant::NUMERIC_ZERO) {
            $gbitems = array();
            $query = GbItems::getbyCourseId($cid);
            foreach ($query as $gbItem) {
                $gbitems[] = $gbItem['id'];
            }

            if (count($assesses) > AppConstant::NUMERIC_ZERO) {
                filehandler::deleteasidfilesbyquery2('userid', $tounenroll, $assesses);
                AssessmentSession::deleteSessionByAssessmentId($assesses, $tounenroll);
                Exceptions::deleteByAssessmentIdAndUid($assesses, $tounenroll);
            }
            if (count($drills) > AppConstant::NUMERIC_ZERO) {
                DrillassessSessions::deleteDrillSession($drills, $tounenroll);
            }
            if (count($exttools) > AppConstant::NUMERIC_ZERO) {
                $gradeType = 'exttool';
                Grades::deleteGradesUsingType($gradeType, $exttools, $tounenroll);
            }
            if (count($gbitems) > AppConstant::NUMERIC_ZERO) {
                $gradeType = 'offline';
                Grades::deleteGradesUsingType($gradeType, $gbitems, $tounenroll);
            }
            if (count($threads) > AppConstant::NUMERIC_ZERO) {
                ForumView::deleteViewRelatedToCourse($threads, $tounenroll);
            }
            if (count($wikis) > AppConstant::NUMERIC_ZERO) {
                WikiView::deleteWikiRelatedToCourse($wikis, $tounenroll);
            }

            if (count($stugroups) > AppConstant::NUMERIC_ZERO) {
                StuGroupMembers::deleteMemberFromCourse($tounenroll, $stugroups);
            }
        }
        if ($delforum && count($forums) > AppConstant::NUMERIC_ZERO) {
            ForumPosts::deleteForumRelatedToCurse($forums);
            $query = ForumPosts::selectForumPosts($forums);
            foreach ($query as $post) {
                filehandler::deleteallpostfiles($post['id']);
            }
            ForumPosts::deleteForumPostByForumList($forums);
            if (count($tounenroll) > AppConstant::NUMERIC_ZERO) {
                $gradeType = 'forum';
                Grades::deleteGradesUsingType($gradeType, $forums, $tounenroll);
            }
        }
        if ($delwikirev === AppConstant::NUMERIC_ONE && count($wikis) > AppConstant::NUMERIC_ZERO) {
            WikiRevision::deleteWikiRivision($wikis);
        } else if ($delwikirev === AppConstant::NUMERIC_TWO && count($grpwikis) > AppConstant::NUMERIC_ZERO) {
            WikiRevision::deleteWikiRivision($grpwikis);
        }
        if ($deloffline) {
            GbItems::deleteByCourseId($cid);
        }
        if ($withwithdraw == 'unwithdraw' && count($assesses) > AppConstant::NUMERIC_ZERO) {
            Questions::updateWithdrawn($assesses);
        }
        if ($withwithdraw == 'remove' || $usereplaceby) {
            $msg = UpdateAssessUtility::updateassess($cid, $withwithdraw == 'remove', $usereplaceby);
        }

        if (count($tounenroll) > AppConstant::NUMERIC_ZERO) {
            foreach ($tounenroll as $studentId) {
                Student::deleteStudent($studentId, $cid);
            }
//            if($cid != $checkedCourseId){
//                print_r('Flash message');
//            }
            LoginLog::deleteCourseLog($tounenroll, $cid);
            ContentTrack::deleteUsingCourseAndUid($tounenroll, $cid);
        }
    }
}