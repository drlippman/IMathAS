<?php
use yii\helpers\Html;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Send Mass E-mail';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
if($gradebook == AppConstant::NUMERIC_ONE){
    $this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
}else{
    $this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
}
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<form name="myEmailForm" action="roster-email" method="post" id="roster-form">
<div class="student-roster-email">
    <input type="hidden" name="isEmail" value="1"/>
    <input type="hidden" name="gradebook" value="<?php echo $gradebook ?>"/>
    <input type="hidden" name="studentInformation" value='<?php echo $studentDetails ?>'/>
    <input type="hidden" name="courseId" value='<?php echo $course->id ?>'/>

    <h2><b>Send Mass E-mail</b></h2>
    <div>
        <span class="col-md-2"><b>Subject</b></span>
        <span class="col-md-8"><?php echo '<input class="textbox subject form-control" type="text" name="subject">'; ?></span>
    </div>
    <br><br>
    <div class="gb">
        <span class="col-md-2"><b>Message</b></span>
        <?php echo "<span class='left col-md-10'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'>";
        echo "</textarea></div></span><br>"; ?>
    </div>
    <p class="col-md-2"></p>
    <p class="col-md-10"><i>Note:</i> <b>FirstName</b> and <b>LastName</b> can be used as form-mail fields that will
        autofill with each student's first/last name</p>
    <div>
        <span class="col-md-2"><b>Send copy to</b></span>
        <span class="col-md-10"><input type="radio" name="emailCopyToSend" id="self" value="singleStudent"> Only Students<br>
            <input type="radio" name="emailCopyToSend" id="self" value="selfStudent" checked="checked"> Students and you<br>
            <input type="radio" name="emailCopyToSend" id="self" value="allTeacher"> Students and all instructors of this course</span>
    </div>
    <span class="col-md-2 select-text-margin"><b>Limit send </b></span>
    <span class="roster-assessment">
	 <p class="col-md-3">To students who haven't completed</p>
	  <select name="roster-assessment-data" id="roster-assessment-data" class="col-md-4 select-text-margin">
          <option value='0'>Don't limit - send to all</option>;
          <?php foreach ($assessments as $assessment) { ?>
          <option value="<?php echo $assessment->id ?>">
              <?php echo ucfirst($assessment->name);?>
              </option><?php } ?>
      </select>
    </span>
    <div class="col-lg-offset-2 col-md-10"><br>
        <input type="submit" class="btn btn-primary" id="email-button" value="Send Email">
        <?php if($gradebook == AppConstant::NUMERIC_ONE){?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id)  ?>">Back</a>
        <?php }else {?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
        <?php } ?>
    </div>
    <div>
        <span><p class="col-md-3">Unless limited, message will be sent to:</p></span>
        <span class="col-md-12"><?php foreach (unserialize($studentDetails) as $studentDetail) { ?>
                <?php echo "<li>".ucfirst($studentDetail['LastName']).", ". ucfirst($studentDetail['FirstName'])." (". ($studentDetail['SID']).")</li>" ?>
            <?php } ?>
        </span>
    </div>
</div>
</form>