<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = 'New Message';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
//$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
<div class="student-roster-email">
    <h2><b>Send Mass E-mail</b></h2>
    <div>
        <span class="col-md-2"><b>Subject</b></span>
        <span class="col-md-8"><?php echo '<input class="textbox subject" type="text">'; ?></span>
    </div>
    <br><br>
    <div class="gb">
        <span class="col-md-1"><b>Message</b></span>
        <?php echo "<span class='left col-md-11'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'>";
        echo "</textarea></div></span><br>"; ?>
    </div>
    <p class="col-md-2"></p>
    <p class="col-md-10"><i>Note:</i> <b>FirstName</b> and <b>LastName</b> can be used as form-mail fields that will
        autofill with each student's first/last name</p>
    <div>
        <span class="col-md-2"><b>Send copy to</b></span>
        <span class="col-md-10"><input type="radio" name="only-student" id="self" value="none"> Only Students<br>
            <input type="radio" name="student" id="self" value="self" checked="checked"> Students and you<br>
            <input type="radio" name="instuctor" id="self" value="allt"> Students and all instructors of this course</span>
    </div>
    <span class="col-md-2 select-text-margin"><b>Limit send </b></span>
    <span class="roster-assessment">
	 <p class="col-md-3">To students who haven't completed</p>
	  <select name="roster-data" id="roster-data" class="col-md-4 select-text-margin">
          <option value='0'>Don't limit - send to all</option>;
          <?php foreach ($assessments as $assessment) { ?>
          <option value="<?php echo $assessment->id ?>">
              <?php echo ucfirst($assessment->name);?>
              </option><?php } ?>
      </select>
    </span>
    <div class="col-lg-offset-2 col-md-12"><br>
        <a class="btn btn-primary" id="mess">Send Message</a>
    </div>
    <div>
        <span><p class="col-md-3">Unless limited, message will be sent to:</p></span>
        <span class="col-md-12"><?php foreach ($studentDetails as $studentDetail) { ?>
            <?php echo "<li>$studentDetail->FirstName, $studentDetail->LastName ($studentDetail->SID)</li>" ?>
            <?php } ?>
        </span>
    </div>
</div>