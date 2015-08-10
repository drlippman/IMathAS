<?php
use app\components\AppUtility;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppConstant;

$this->title = AppUtility::t('Manage Exception', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]); ?>
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
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
    <?php if ($gradebook == AppConstant::NUMERIC_ONE){ ?>
    <form action="make-exception?cid=<?php echo $course->id ?>&gradebook=1" method="post" id="roster-form">
        <?php }else { ?>
        <form action="make-exception?cid=<?php echo $course->id ?>" method="post" id="roster-form">
            <?php } ?>
            <input type="hidden" name="isException" value="1"/>
            <input type="hidden" name="gradebook" value="<?php echo $gradebook ?>"/>
            <input type="hidden" name="courseid" value="<?php echo $course->id ?>"/>
            <input type="hidden" name="studentInformation" value='<?php echo $studentDetails ?>'/>
            <input type="hidden" name="section" value='<?php echo $section ?>'/>
            <div>
                <?php if (sizeof((unserialize($studentDetails))) == 1) {
                    foreach (unserialize($studentDetails) as $studentDetail) {
                        echo "<div><h3 class='name pull-left'>" . ucfirst($studentDetail['LastName']) . ", " . ucfirst($studentDetail['FirstName']);
                        if ($section != "" && $section != 'null')
                            echo "</h3><h4 class='pull-left margin-5'>&nbsp; (" . AppUtility::t('section: ', false) . $section . ")</h4><br class='form'>";
                        else {
                            echo " </h3></div><br class='form'>";
                        }
                    }
                }
                ?>
                <?php
                if ($existingExceptions){
                echo "<div class='exception-list items-list'><h4>" . AppUtility::t('Existing Exceptions', false) . "</h4><p>" . AppUtility::t('Select exceptions to clear', false) . "</p>"
                ?>
                <div>
                    <?php
                    foreach ($existingExceptions as $entry) {
                        echo "<ul><li>" . $entry['Name'] . "<ul>";
                        foreach ($entry['assessments'] as $singleAssessment) {
                            echo "<li><div class='checkbox override-hidden'><label><input type='checkbox' name='clears[]' value='{$singleAssessment['exceptionId']}'><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>" . " " . ucfirst($singleAssessment['assessmentName']) . " (" . "{$singleAssessment['exceptionDate']}" . ")";
                            if ($singleAssessment['waiveReqScore'] == 1) {
                                echo ' <i>(' . AppUtility::t('waives prereq', false) . ')</i>';
                            }
                            echo "</label></div></li>";
                        }
                        echo "</ul></li>";
                        ?>
                        <?php echo "</ul>";
                    }
                    echo "</div><input type='submit'  class='btn btn-primary record-submit clear-exception' id='change-record' value='Record Changes'></div>";
                    }
                    else {
                        echo "<div class='alert alert-danger alert-margin-bottom'>".AppUtility::t('No exceptions currently exist for the selected students.', false)."</div>";
                    } ?>
                    <br>
                    <div class="items-list">
                        <h4><?php AppUtility::t('Make New Exception')?></h4>
                        <br>
                        <div class="pull-left col-sm-5" id="datePicker-id1">
                            <span class="pull-left col-md-4"><?php AppUtility::t('Available After')?></span>
                            <span class="pull-left col-md-8">
                            <?php
                            echo DatePicker::widget([
                                'name' => 'startDate',
                                'options' => ['placeholder' => 'Select start date ...'],
                                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                'value' => date("m/d/Y"),
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'mm/dd/yyyy']
                            ]);
                            ?>
                                </span>
                        </div>
                        <div class="pull-left col-sm-7" id="timepicker-id">
                            <span class="pull-left col-md-1"><?php AppUtility::t('at')?></span>
                         <span class="pull-left col-md-8">
                            <?php
                            echo TimePicker::widget([
                                'name' => 'startTime',
                                'options' => ['placeholder' => 'Select start time ...'],
                                'convertFormat' => true,
                                'value' => date('g:i A'),
                                'pluginOptions' => [
                                    'format' => "m/d/Y g:i A",
                                    'todayHighlight' => true,
                                ]
                            ]);
                            ?>
                             </span>
                        </div>
                        <br><br>
                        <div class="pull-left col-sm-5" id="datePicker-id2">
                            <span class="pull-left col-md-4"><?php AppUtility::t('Available Until')?></span>
                             <span class="pull-left col-md-8">
                            <?php
                            echo DatePicker::widget([
                                'name' => 'endDate',
                                'options' => ['placeholder' => 'Select end date ...'],
                                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                'value' => date("m/d/Y", strtotime("+1 week")),
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'mm/dd/yyyy']
                            ]);
                            ?>
                                  </span>
                        </div>
                        <div class="pull-left col-sm-7" id="timepicker-id1">
                            <span class="pull-left col-md-1"><?php AppUtility::t('at')?></span>
                            <span class="pull-left col-md-8">
                            <?php
                            echo TimePicker::widget([
                                'name' => 'endTime',
                                'options' => ['placeholder' => 'Select time ...'],
                                'convertFormat' => true,
                                'value' => "10:00 AM",
                                'pluginOptions' => [
                                    'format' => "m/d/Y g:i A",
                                    'todayHighlight' => true,
                                ]
                            ]);
                            ?>
                                </span>
                        </div>
                        <br><br>
                        <p><?php AppUtility::t('Set Exception for assessments')?></p>
                        <ul class='assessment-list'>
                            <?php foreach ($assessments as $assessment) { ?>
                                <?php
                                echo "<li><div class='checkbox override-hidden'><label><input type='checkbox' name='addExc[]' value='{$assessment->id}'><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>" . " " . ucfirst($assessment->name)."</label></div></li>";;
                                ?>
                            <?php } ?>
                        </ul>
                        <input type="submit" class="btn btn-primary record-submit create-exception" id="change-record"
                               value="<?php AppUtility::t('Record Changes')?>">
                        <?php if ($gradebook == AppConstant::NUMERIC_ONE) { ?>
                            <a class="btn btn-primary back-btn"
                               href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course->id) ?>"><?php AppUtility::t('Back')?></a>
                        <?php } else { ?>
                            <a class="btn btn-primary back-btn"
                               href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid=' . $course->id) ?>"><?php AppUtility::t('Back')?></a>
                        <?php } ?>
                        <br>
                        <div class="padding-top-five">
                            <div class='checkbox override-hidden padding-top-five'><label class="padding-left-zero"><input type="checkbox" name="forceReGen"><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span><?php AppUtility::t('Force student to work on new versions of all
                                questions? Students will keep any scores earned, but must work new versions of questions to improve score.')?></label></div>
                            <div class='checkbox override-hidden padding-top-five'><label class="padding-left-zero"><input type="checkbox" name="forceClear"><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>
                                <?php AppUtility::t('Clear student\'s attempts? Students will')?>
                                <b><?php AppUtility::t('not')?></b><?php AppUtility::t('keep any scores earned, and must rework all problems.')?>
                                </label></div>
                            <div class='checkbox override-hidden padding-top-five'><label class="padding-left-zero"><input type="checkbox" name="eatLatePass"><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>
                                 <?php AppUtility::t('Deduct')?><input type="input" name="latePassN" size="1" value="1"> <?php AppUtility::t('LatePass(es) from each student.')?>
                                <?php echo $latePassMsg ?></label></div>
                            <div class='checkbox override-hidden padding-top-five'><label class="padding-left-zero"><input type="checkbox" name="waiveReqScore"><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>
                                 <?php AppUtility::t('Waive "show based on an another assessment" requirements, if applicable.')?></label></div>
                            <div class='checkbox override-hidden padding-top-five'><label class="padding-left-zero"><input type="checkbox" name="sendMsg"><span class='cr margin-bottom'><i class='cr-icon fa fa-check'></i></span>
                                 <?php AppUtility::t('Send message to these students?')?></label></div>
                        </div>
                    </div>
                    <?php if (sizeof((unserialize($studentDetails))) != 1) { ?>
                        <div>
                            <span><p><h4><?php AppUtility::t('Students Selected')?></h4></span>
                            <ul>
                            <span class="col-md-12"><?php foreach (unserialize($studentDetails) as $studentDetail) { ?>
                                    <?php echo "<li>" . ucfirst($studentDetail['LastName']) . "," . ucfirst($studentDetail['FirstName']) . " (" . ($studentDetail['SID']) . ")</li>" ?>
                                <?php } ?>
                            </span>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
        </form>
</div>
</div>