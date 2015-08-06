<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

$this->title = 'Add Offline Grades';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<legend>Add Offline Grades</legend>
<fieldset xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
    <form enctype="multipart/form-data" method=post action="add-grades?cid=<?php echo $course->id;?>">
        <fieldset xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">

            <span class=form>Name:</span><span class=formright><input type=text name="name"
                    /></span><br
                class="form"/>

            <span class=form>Points:</span><span class=formright><input type=text name="points" size=3
                                                                        value="0"/></span><br
                class="form"/>
            <span class=form>Show grade to students after:</span><span class=formright><input type=radio
                                                                                              name="sdate-type"
                                                                                              value="0"
                    checked
                /> Always<br/>
<input type=radio name="sdate-type" value="sdate" <?php if ($showdate != '0') {
    echo "checked=1";
} ?>/>
                <div class="item-alignment">


                    <?php

                    echo '<div class = "col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left"> at </label>';
                    echo '<div class=" col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => date('g:i m'),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>


                </div>
<br>

            <div class="item-alignment">
                <div class=col-lg-2>Gradebook Category:</div>
                <div class=col-lg-10>
                    <?php AssessmentUtility::writeHtmlSelect("gradebook-category", $gbcatsId, $gbcatsLabel, 0, "Default", 0); ?>
                </div>
                <br class=form>
                <?php $page_tutorSelect['label'] = array("No access to scores", "View Scores", "View and Edit Scores");
                $page_tutorSelect['val'] = array(2, 0, 1); ?>
            </div>
            <span class=form>Count: </span>
			<span class="formright">
				<input type=radio name="cntingb" checked
                       value="1" /> Count in Gradebook<br/>
				<input type=radio name="cntingb"
                       value="0" /> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb"
                       value="3" /> Don't count in grade total<br/>
				<input type=radio name="cntingb"
                       value="2"/> Count as Extra Credit
			</span><br class=form>
                <?php $page_tutorSelect['label'] = array("No access to scores", "View Scores", "View and Edit Scores");
                $page_tutorSelect['val'] = array(2, 0, 1); ?>

                <span class="form">Tutor Access:</span>
				<span class="formright">
	<?php
    AssessmentUtility::writeHtmlSelect("tutoredit", $page_tutorSelect['val'], $page_tutorSelect['label'], $checkboxesValues['tutoredit']);
    echo '<input type="hidden" name="gradesecret" value="' . $checkboxesValues['gradesecret'] . '"/>';
    ?>
			</span><br class="form"/>

            <div class="item-alignment">
                <div class="col-lg-2">Use Scoring Rubric</div>
                <div class=col-lg-10>
                    <?php AssessmentUtility::writeHtmlSelect('rubric', $rubricsId, $rubricsLabel, 0, 'None', 0); ?>
                    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid=' . $course->id) ?>">Add
                        new
                        rubric</a> | <a
                        href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/edit-rubric?cid=' . $course->id) ?>">Edit
                        rubrics</a>
                </div>
                <br class=form>
            </div>

            <div class="item-alignment">
                <?php if (count($pageOutcomesList) > 0) { ?>
                <div class="col-lg-2">Associate Outcomes:</div>
                <div class="col-lg-10">
                    <?php
                    $gradeoutcomes = array();
                    AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
                    <br class="form"/>
                    <?php } ?>
                    <br class=form>
                </div>
            </div>


            <span class="form">Upload grades?:</span>
				<span class="formright">
	 <input type="checkbox" value="1" name="uploade-grade">
                    <input type="submit" value="Submit">
			</span><br class="form"/>


            <span class="form">Assessment snapshot?:</span>

				<span class="formright">
 <input type="checkbox" value="1" name="snapshot" class="assessment_snapshot">
			</span><br class="form"/>
                <div class="change-assessment-snapshot-content">
                    <div class="item-alignment">
                        <div class="col-lg-2">Assessment To Snapshot</div>
                        <div class=col-lg-10>
                            <?php AssessmentUtility::writeHtmlSelect('assessment', $assessmentId, $assessmentLabel, 0); ?>
                        </div>
                        <br class=form>
                    </div>

                    <span class=form>Grade type: </span>
			<span class="formright">
				<input type=radio name="grade-type" checked
                       value="0"/> Current score <br/>
				<input type=radio name="grade-type"
                       value="1"/> Participation: give full credit if ≥ <input type="text" size="4"
                                                                               name="grade-type-total" value="100"> % of problems attempted and ≥ <input
                    type="text" size="4" name="grade-type-points" value="0"> points earned.<br/>
			</span><br class=form>

                </div>
            <div class="change-non-assessment-snapshot-content">
                <div>
                    <input type="button" size="1" value="Expand Feedback Boxes" id="expand-button"
                           class="btn btn-primary back-button-change-student-info"
                           onclick="togglefeedbackTextFields(-1)"/>
                    <input type="button" value="Shrink Feedback Boxes" id="shrink-button"
                           class="btn btn-primary back-button-change-student-info"
                           onclick="togglefeedbackTextFields(0)"/>
                    <a class="btn btn-primary back-button-change-student-info" onclick="togglequickadd(this)">Use
                        Quicksearch Entry</a>
                    <!--            <button type="button" id="useqa" onclick="togglequickadd(this)"></button>-->
                </div>

                <br>

                <div><label>Add/Replace to all grades:</label><input type="text" size="3" id="txt_add"
                                                                     class="col-lg-offset-1" value="1"/>
                    <input type="button" value="Add" class="btn btn-primary" onclick="addReplaceMultiplyTextValue(1)"/>
                    <input type="button" value="Replace" class="btn btn-primary"
                           onclick="addReplaceMultiplyTextValue(2)"/>
                    <input type="button" value="Multiply" class="btn btn-primary"
                           onclick="addReplaceMultiplyTextValue(3)"/>
                </div>

                <br>

                <div><label>Add/Replace to all feedback:</label><input type="text" id="feedback_txt"
                                                                       class="col-lg-offset-1"/>
                    <input type="button" value="Append" class="btn btn-primary" onclick="appendPrependReplaceText(1)"/>
                    <input type="button" value="Prepend" class="btn btn-primary" onclick="appendPrependReplaceText(3)"/>
                    <input type="button" value="Replace" class="btn btn-primary" onclick="appendPrependReplaceText(2)"/>
                </div>
                <br>

                <div class="clear"></div>
                <table class="student-data table table-bordered table-striped table-hover data-table"
                       bPaginate="false">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Section</th>
                        <th>Grades</th>
                        <th>Feedback</th>
                    </tr>
                    </thead>
                    <tbody>

                    <tr id="quickadd" style="display:none;">
                        <td><input type="text" id="project"/></td>
                        <td></td>

                        <td><input type="text" id="project-id" size="3" onblur="this.value = doonblur(this.value);"
                                   onkeydown="return qaonenter(event,this);"/></td>
                        <td><textarea id="qafeedback" rows="1" cols="40"></textarea>';
                            <input type="button" value="Next" onfocus="addsuggest()"/></td>
                    </tr>

                    <?php
                    foreach ($studentInformation as $singleStudentInformation) { ?>
                        <tr>
                            <td><?php echo $singleStudentInformation['Name'] ?></td>
                            <td><?php echo $singleStudentInformation['Section'] ?></td>
                            <td><input type="text"
                                       name="grade_text[<?php echo $singleStudentInformation['StudentId'] ?>]"
                                       class="latepass-text-id" size="4"/></td>
                            <td><textarea type="text"
                                          name="feedback_text[<?php echo $singleStudentInformation['StudentId'] ?>]"
                                          class="feedback-text-id"></textarea></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="form-group">
                <div class=" col-lg-8 display_field">
                    <input type="submit" value="Save">
                    <a class="btn btn-primary back-button-change-student-info"
                       href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid=' . $courseId) ?>">Back</a>
                </div>
            </div>


    </form>
</fieldset>