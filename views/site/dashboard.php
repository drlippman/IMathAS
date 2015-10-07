<?php
use app\components\AppUtility;
?>
    <title>IMathAS</title>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

<?php
use app\components\AppConstant;
?>
<div class="tab-content shadowBox non-nav-tab-item">
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
<!--        --><?php
//        if ($user->rights > AppConstant::GUEST_RIGHT) {
//            echo $this->render('_fullMenu');
//        } else {
//            echo $this->render('_guestMenu');
//        } ?>
        <div class="pagetitle col-lg-12" id="headerhome"><h2>Welcome to IMathAS, <?php echo AppUtility::getFullName($user->FirstName, $user->LastName); ?><span class="red"></span>
        </div>
        <div class="col-lg-12" id="homefullwidth">
            <?php
            if ($user->rights > AppConstant::GUEST_RIGHT)
            {
                if ($user->rights > AppConstant::TEACHER_RIGHT)
                {
                    echo $this->render('_adminCourseTeaching',['teachers' => $teachers, 'msgRecord' => $msgRecord, 'myRights' => $user->rights, 'teachCourse' => $teachCourse]);
                } elseif ($user->rights > AppConstant::STUDENT_RIGHT)
                {
                    echo $this->render('_courseTeaching',['teachers' => $teachers, 'msgRecord' => $msgRecord, 'teachCourse' => $teachCourse]);
                } elseif ($user->rights > AppConstant::STUDENT_RIGHT)
                {
                    echo $this->render('_courseTutoring', ['tutors' => $tutors]);
                }
                echo $this->render('_courseTaking', ['students' => $students, 'msgRecord' => $msgRecord, 'studCourse' => $studCourse]);
            }else
            {

            } ?>
        </div>
        <div class="clear"></div>
    </div>
</div>
</div>