<?php
namespace app\controllers;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

$this->title = 'Make Exception';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = ['label' => 'List Students', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher');
?>
<div id="headermassexception" class="pagetitle"><h2>Manage Exceptions</h2></div>
<form action="make-exception" method="post" id="roster-form">
    <div class="student-roster-exception">
        <input type="hidden" name="isException" value="1"/>
        <input type="hidden" name="courseid" value="<?php echo $course->id ?>"/>
        <div>
            <p><h4>Existing Exceptions</h4></p>
            <p>Select exceptions to clear</p>
            <ul><li>Kawade, Rohan<ul><li><input type="checkbox" name="clears[]" value="21">rr (06/09/15 8:14 pm - 06/16/15 10:00 am)</li></ul></li></ul>
            <input type="submit"  class="btn btn-primary " value="Record Changes">
        </div>
        <div>
            <p>
            <p><h4>Make New Exception</h4></p>
            <span class="form select-text-margin">Available After:</span>

              <div class="col-lg-3 pull-left" id="datepicker-id" >
                  <?php
                  echo DatePicker::widget([
                      'name' => 'First_Date_Picker',
                      'type' => DatePicker::TYPE_COMPONENT_APPEND,
                      'value' => date("m-d-Y"),
                      'pluginOptions' => [
                          'autoclose' => true,
                          'format' => 'mm-dd-yyyy' ]
                  ]);
                  ?>
              </div><div class="end pull-left select-text-margin right">at</div>
            <div class="col-lg-4" id="timepicker-id" >
                <?php
                echo TimePicker::widget([
                    'name' => 'datetime_1',
                    'options' => ['placeholder' => 'Select operating time ...'],
                    'convertFormat' => true,
                    'pluginOptions' => [
                        'format' => 'd-M-Y g:i A',
                        'todayHighlight' => true
                    ]
                ]);
                ?>
            </div>
            <br class="form">
            </p>
            <span class="form select-text-margin">Available Until:</span>

                 <div class="col-lg-3 pull-left" id="datepicker-id1" >
                         <?php
                         echo DatePicker::widget([
                             'name' => 'Second_Date_Picker',
                             'type' => DatePicker::TYPE_COMPONENT_APPEND,
                             'value' => date("m-d-Y",strtotime("+1 week -1 day")),
                             'pluginOptions' => [
                                 'autoclose' => true,
                                 'format' => 'mm-dd-yyyy' ]
                         ]);
                         ?>
                 </div><div class="end pull-left select-text-margin right">at</div>
            <div class="col-lg-4" id="timepicker-id1" >
                <?php
                echo TimePicker::widget([
                    'name' => 'datetime_2',
                    'options' => ['placeholder' => 'Select operating time ...'],
                    'convertFormat' => true,
                    'value' => '10:00 AM',
                    'pluginOptions' => [
                        'format' => 'd-M-Y g:i A',
                        'todayHighlight' => true
                    ]
                ]);
                ?>
            </div>
            </span>
            <br class="form">
            <p>Set Exception for assessments:</p>
            <ul>
                <?php foreach ($assessments as $assessment) { ?>
                <?php echo "<li><input type='checkbox' name='{$assessment->name}' value='{$assessment->id}'>".' '. ucfirst($assessment->name)."</li>";?>
                <?php } ?>
            </ul>
            <input type="submit" class="btn btn-primary " id="change-record" value="Record Changes">
        </div>
        <br>
        <div>
            <p><input type="checkbox" name="forceregen">Force student to work on new versions of all questions? Students will keep any scores earned, but must work new versions of questions to improve score.</p>
            <p><input type="checkbox" name="forceclear">Clear student's attempts?  Students will <b>not</b>  keep any scores earned, and must rework all problems.</p>
            <p><input type="checkbox" name="eatlatepass">Deduct <input type="input" name="latepassn" size="1" value="1">  LatePass(es) from each student. These students all have 0 latepasses.</p>
            <p><input type="checkbox" name="waivereqscore"> Waive "show based on an another assessment" requirements, if applicable.</p>
            <p><input type="checkbox" name="sendmsg"> Send message to these students?</p>
        </div>
        <div>
            <span><p><h4>Students Selected:</h4></span><ul>
            <span class="col-md-12"><?php foreach (unserialize($studentDetails) as $studentDetail) { ?>
               <?php echo "<li>".ucfirst($studentDetail['LastName']).",". ucfirst($studentDetail['FirstName'])." (". ($studentDetail['SID']).")</li>" ?>
           <?php } ?></ul>
        </span>
        </div>

    </div>
</form>