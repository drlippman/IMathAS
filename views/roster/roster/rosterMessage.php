<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Send Mass Message', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php
    if($gradebook == AppConstant::NUMERIC_ONE){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id]]);
    } else {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid='.$course->id]]);
    }
    ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content inner-sub-content shadowBox"">
<form name="myForm" action="roster-message" method="post" id="roster-form">
    <div class="student-roster-message">
        <input type="hidden" name="isMessage" value="1"/>
        <input type="hidden" name="gradebook" value="<?php echo $gradebook ?>"/>
        <input type="hidden" name="courseid" value="<?php echo $course->id ?>"/>
        <input type="hidden" name="studentInformation" value='<?php echo $studentDetails ?>'/>
        <div>
            <div class="col-md-2 form-content"><b><?php AppUtility::t('Subject')?></b></div>
            <div class="col-md-8 form-content"><?php echo '<input class="textbox subject form-control" type="text" name="subject">'; ?></div>
        </div>
        <div class="col-md-12 form-content">
            <div class="col-md-2 form-content"><b><?php AppUtility::t('Message')?></b></div>
            <?php echo "<div class='left col-md-10 form-content'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'>"; echo "</textarea></div></div><br>"; ?>
        </div>
    </div>
    <div>
        <p class="col-md-10 col-md-offset-2 form-content"><i><?php AppUtility::t('Note:')?></i> &nbsp;<b><?php AppUtility::t('FirstName')?></b>&nbsp;<?php AppUtility::t('and')?> <b>&nbsp;<?php AppUtility::t('LastName')?></b>&nbsp;<?php AppUtility::t('can be used as form-mail fields that will autofill with each student\'s first/last name')?>
        </p>
    </div>
    <div>
        <div class="col-md-2 form-content"><b><?php AppUtility::t('Send copy to')?></b>
        </div>
        <div class="col-md-10 form-content">
            <input type="radio" name="messageCopyToSend" id="self" value="onlyStudents"> <?php AppUtility::t('Only Students')?><br>
            <input type="radio" name="messageCopyToSend" id="self" value="selfAndStudents" checked="checked"> <?php AppUtility::t('Students and you')?><br>
            <input type="radio" name="messageCopyToSend" id="self" value="teachersAndStudents"> <?php AppUtility::t('Students and all instructors of this course')?>
        </div>
    </div>
    <div>
        <div class="col-md-2 form-content"><b><?php AppUtility::t('Save sent messages?')?></b></div>
        <div class="col-md-10 form-content"><input type="checkbox" name="isChecked" id="save-sent-message" checked="true"></div>
    </div>
    <div>
        <div class="col-md-2 form-content"><b><?php AppUtility::t('Limit send ')?></b></div>
        <div class="roster-assessment form-content">
            <p class="col-md-3 form-content"><?php AppUtility::t('To students who haven\'t completed')?></p>
            <p class="form-content col-md-4">
                <select name="roster-assessment-data" id="roster-data" class="form-control">
                    <option value='0'><?php AppUtility::t('Don\'t limit - send to all')?>
                    </option>;
                    <?php foreach ($assessments as $assessment) { ?>
                    <option value="<?php echo $assessment->id ?>">
                        <?php echo ucfirst($assessment->name);?>
                    </option>
                        <?php } ?>
                </select>
            </p>
        </div>
    </div>
    <div class="col-md-offset-2 col-md-10 padding-zero"><br>
        <input type="submit" class="btn btn-primary " id="message-button" value="<?php AppUtility::t('Send Message')?>">
        <?php if($gradebook == AppConstant::NUMERIC_ONE){?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
        <?php }else {?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
        <?php } ?>
    </div>
    <div>
        <div><p class="col-md-12 form-content"><?php AppUtility::t('Unless limited, message will be sent to:')?></p></div>
        <div class="col-md-11 col-md-offset-1 form-content list"><?php foreach (unserialize($studentDetails) as $studentDetail) { ?>
               <?php echo "<li>".ucfirst($studentDetail['LastName']).", ". ucfirst($studentDetail['FirstName'])." (". ($studentDetail['SID']).")</li>" ?>
           <?php } ?>
        </div>
    </div>
</form>
</div>

