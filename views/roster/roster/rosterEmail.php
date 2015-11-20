<?php
use yii\helpers\Html;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = AppUtility::t('Send Mass E-mail', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
<?php
if($gradebook == AppConstant::NUMERIC_ONE){
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id]]);
} else {
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid='.$course->id]]);
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
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox roster-email-shadowbox-padding">
<form name="myEmailForm" action="roster-email" method="post" id="roster-form">
<div class="student-roster-email">
    <input type="hidden" name="isEmail" value="1"/>
    <input type="hidden" name="gradebook" value="<?php echo $gradebook ?>"/>
    <input type="hidden" name="studentInformation" value='<?php echo $studentDetails ?>'/>
    <input type="hidden" name="courseId" value='<?php echo $course->id ?>'/>
    <div>
        <div class="col-md-1 col-sm-2 form-content select-text-margin"><b><?php AppUtility::t('Subject')?></b></div>
        <div class="col-md-11 col-sm-10 padding-left-fifteen form-content"><?php echo '<input class="textbox subject form-control" type="text" name="subject">'; ?></div>
    </div>
    <div class="col-md-12 col-sm-12 form-content">
        <div class="col-md-1 col-sm-2 padding-top-five form-content">
            <b><?php AppUtility::t('Message')?></b>
        </div>
        <?php echo "<div class='col-md-11 col-sm-10 padding-left-fifteen form-content'>
        <div class= 'editor email-message-textarea'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'>";
        echo "</textarea></div></div><br>"; ?>
    </div>
    <div>
    <p class="col-md-11 col-sm-10 padding-left-fifteen col-md-offset-1 col-sm-offset-2 form-content"><i><?php AppUtility::t('Note:')?></i> &nbsp;<b><?php AppUtility::t('FirstName')?></b>&nbsp;<?php AppUtility::t('and')?> <b>&nbsp;<?php AppUtility::t('LastName')?></b>&nbsp;<?php AppUtility::t('can be used as form-mail fields that will autofill with each student\'s first/last name')?>
    </p>
    </div>
    <div>
        <div class="col-md-1 col-sm-2 form-content"><b><?php AppUtility::t('Send copy to')?></b>
        </div>
        <div class="col-md-11 col-sm-10 form-content">
            <span class="col-md-11 col-sm-11"><input type="radio" name="emailCopyToSend" id="self" value="singleStudent"><span class="margin-left-five"> <?php AppUtility::t('Only Students')?></span></span>
            <span class="col-md-11 col-sm-11 margin-top-five"><input type="radio" name="emailCopyToSend" id="self" value="selfStudent" checked="checked"><span class="margin-left-five"> <?php AppUtility::t('Students and you')?></span></span>
            <span class="col-md-11 col-sm-11 margin-top-five"><input type="radio" name="emailCopyToSend" id="self" value="allTeacher"><span class="margin-left-five"> <?php AppUtility::t('Students and all instructors of this course')?></span></span>
        </div>
    </div>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-ten">
    <div class="col-md-1 col-sm-2 form-content padding-top-one-pt-two"><b><?php AppUtility::t('Limit send ')?></b> </div>
    <div class="col-md-11 col-sm-10 roster-assessment">
        <span class="floatleft padding-top-one-pt-two padding-right-pt-five-em">
            <?php AppUtility::t('To students who haven\'t completed')?>
        </span>
        <span class="form-content col-md-4 col-sm-4">
            <select name="roster-assessment-data" id="roster-assessment-data" class="form-control">
              <option value='0'><?php AppUtility::t('Don\'t limit - send to all')?>
              </option>
              <?php foreach ($assessments as $assessment) { ?>
              <option value="<?php echo $assessment->id ?>">
                    <?php echo ucfirst($assessment->name);?>
              </option>
                    <?php } ?>
            </select>
        </span>
    </div>
    </div>
    <div class="col-md-offset-1 col-md-11 col-sm-offset-2 col-sm-10 margin-top-twenty">
        <input type="submit" class="btn btn-primary" id="email-button" value="<?php AppUtility::t('Send Email')?>">
        <?php if($gradebook == AppConstant::NUMERIC_ONE){?>
            <a class="btn btn-primary back-btn margin-left-ten" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
        <?php }else {?>
            <a class="btn btn-primary back-btn margin-left-ten" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
        <?php } ?>
    </div>
    <div class="col-md-12 col-sm-12 padding-left-zero margin-top-five">
        <div class="col-md-12 col-sm-12 form-content"><?php AppUtility::t('Unless limited, message will be sent to')?></div>
        <div class="col-md-11 col-md-offset-1 col-sm-10 col-sm-offset-2 form-content list"><?php foreach (unserialize($studentDetails) as $studentDetail) { ?>
                <?php echo "<li>".ucfirst($studentDetail['LastName']).", ". ucfirst($studentDetail['FirstName'])." (". ($studentDetail['SID']).")</li>" ?>
            <?php } ?>
        </div>
    </div>
</div>
</form>
</div>
