<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

if ($overwriteBody == 1) {
    echo $body;

} else {
?>
<script type="text/javascript">
    function nextpage() {
        var aid = document.getElementById('aidselect').value;
        var togo = '<?php echo $addr; ?>&aid=' + aid;
        window.location = togo;
    }
</script>
<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
<div id="headerexception" class="pagetitle"><h2>Make Start/Due Date Exception</h2></div>
<div class="tab-content shadowBox">
    <?php
    echo '<h3>' . $stuname . '</h3>';
    echo $page_isExceptionMsg;
    echo '<p><span class="form">Assessment:</span><span class="formright">';
    AssessmentUtility::writeHtmlSelect("aidselect", $page_courseSelect['val'], $page_courseSelect['label'], $params['aid'], "Select an assessment", "", " onchange='nextpage()'");
    echo '</span><br class="form"/></p>';

    if (isset($params['aid']) && $params['aid'] != '') {
        ?>
        <form method=post
              action="exception?cid=<?php echo $course->id ?>&aid=<?php echo $params['aid'] ?>&uid=<?php echo $params['uid'] ?>&asid=<?php echo $asid; ?>&from=<?php echo $from; ?>">
            <div class="col-md-12 padding-left-zero">
                <div class="col-md-5 padding-left-zero" id="datePicker-id1">
                    <span class="select-text-margin col-md-4"><?php AppUtility::t('Available After') ?></span>
                                <span class="col-md-8">
                                    <?php
                                    echo DatePicker::widget([
                                        'name' => 'sdate',
                                        'options' => ['placeholder' => 'Select start date ...'],
                                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                        'value' => $sdate,
                                        'pluginOptions' => [
                                            'autoclose' => true,
                                            'format' => 'mm/dd/yyyy']
                                    ]);
                                    ?>
                                </span>
                </div>
                <div class="col-md-5 padding-left-zero" id="timepicker-id">
                    <span class="select-text-margin col-md-1 padding-left-five"><?php AppUtility::t('at') ?></span>
                                <span class="col-md-11">
                                <?php
                                echo TimePicker::widget([
                                    'name' => 'stime',
                                    'options' => ['placeholder' => 'Select start time ...'],
                                    'convertFormat' => true,
                                    'value' => $stime,
                                    'pluginOptions' => [
                                        'format' => "m/d/Y g:i A",
                                        'todayHighlight' => true,
                                    ]
                                ]);
                                ?>
                                 </span>
                </div>
            </div>

            <br><br><br>

            <div class="col-md-12 padding-left-zero">
                <div class="col-md-5 padding-left-zero" id="datePicker-id1">
                    <span class="select-text-margin col-md-4"><?php AppUtility::t('Available After') ?></span>
                                <span class="col-md-8">
                                    <?php
                                    echo DatePicker::widget([
                                        'name' => 'edate',
                                        'options' => ['placeholder' => 'Select start date ...'],
                                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                        'value' => $edate,
                                        'pluginOptions' => [
                                            'autoclose' => true,
                                            'format' => 'mm/dd/yyyy']
                                    ]);
                                    ?>
                                </span>
                </div>
                <div class="col-md-5 padding-left-zero" id="timepicker-id">
                    <span class="select-text-margin col-md-1 padding-left-five"><?php AppUtility::t('at') ?></span>
                                <span class="col-md-11">
                                <?php
                                echo TimePicker::widget([
                                    'name' => 'etime',
                                    'options' => ['placeholder' => 'Select start time ...'],
                                    'convertFormat' => true,
                                    'value' => $etime,
                                    'pluginOptions' => [
                                        'format' => "m/d/Y g:i A",
                                        'todayHighlight' => true,
                                    ]
                                ]);
                                ?>
                                 </span>
                </div>
            </div>
            <span class="form"><input type="checkbox" name="forceregen"/></span>
		<span class="formright">Force student to work on new versions of all zquestions?  Students 
		   will keep any scores earned, but must work new versions of questions to improve score.</span><br
                class="form"/>
            <span class="form"><input type="checkbox" name="eatlatepass"/></span>
		<span class="formright">Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es).  
		   Student currently has <?php echo $latepasses; ?> latepasses.</span><br class="form"/>
            <span class="form"><input type="checkbox" name="waivereqscore"/></span>
            <span class="formright">Waive "show based on an another assessment" requirements, if applicable.</span><br
                class="form"/>

            <div class=submit><input type=submit value="<?php echo $savetitle; ?>"></div>
        </form>
    <?php
    }
    echo '</div>';
    }

    ?>
