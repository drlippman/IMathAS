<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = AppUtility::t('Make Start/Due Date Exception', false);
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
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
    </div>
    <div class = "title-container padding-bottom-two-em">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page word-wrap-break-word"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content">
        <?php if($isTeacher) {
            echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']);
        } elseif($isTutor || $isStudent){
            echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'gradebook', 'userId' => $currentUser , 'isTutor'=> $isTutor]);
        }?>
    </div>
<div class="tab-content shadowBox col-md-12 col-sm-12 padding-bottom-two-em">
    <?php
    echo '<div class="col-md-12"><h3 class="col-md-12 col-sm-12">' . $stuname . '</h3></div>';
    echo $page_isExceptionMsg;
    echo '
    <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em">
        <span class="col-md-2 col-sm-2">Assessment</span>
        <span class="padding-left-zero col-md-3 col-sm-4 padding-left-two-pt-three-em">';
        AssessmentUtility::writeHtmlSelect("aidselect", $page_courseSelect['val'], $page_courseSelect['label'], $params['aid'], "Select an assessment", "", " onchange='nextpage()'");
        echo '
        </span>
    </div>';

    if (isset($params['aid']) && $params['aid'] != '') {
        ?>
        <form method=post
              action="exception?cid=<?php echo $course->id ?>&aid=<?php echo $params['aid'] ?>&uid=<?php echo $params['uid'] ?>&asid=<?php echo $asid; ?>&from=<?php echo $from; ?>">
            <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em">
                <span class="select-text-margin col-md-2 col-sm-2 padding-right-zero">
                    <?php AppUtility::t('Available After') ?>
                </span>
                <div class="col-md-3 col-sm-4 padding-left-zero" id="datePicker-id1">
                                <span class="padding-left-zero col-md-12 col-sm-12">
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
                <span class="select-text-margin floatleft"><?php AppUtility::t('at') ?></span>
                <div class="col-md-4 col-sm-5" id="timepicker-id">

                                <span class="col-md-12 col-sm-12">
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

            <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em">
                <span class="select-text-margin col-md-2 col-sm-2 padding-right-zero"><?php AppUtility::t('Available After') ?></span>
                <div class="col-md-3 col-sm-4 padding-left-zero" id="datePicker-id1">
                                <span class="col-md-12 col-sm-12 padding-left-zero">
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
                <span class="select-text-margin floatleft"><?php AppUtility::t('at') ?></span>
                <div class="col-md-4 col-sm-5" id="timepicker-id">

                                <span class="col-md-12 col-sm-12">
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

            <div class="col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10 padding-top-one-pt-five-em padding-left-ten">
                <span class="floatleft">
                    <input type="checkbox" name="forceregen"/>
                </span>
                <span class="col-md-11 col-sm-11">
                    Force student to work on new versions of all zquestions?  Students
                    will keep any scores earned, but must work new versions of questions to improve score.
                </span>
            </div>

            <div class="col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10 padding-top-one-pt-five-em padding-left-ten">
                <span class="floatleft padding-top-five">
                    <input type="checkbox" name="eatlatepass"/>
                </span>
                 <span class="col-md-11 col-sm-11">Deduct
                    <input class="form-control width-ten-per display-inline-block" type="input" name="latepassn" size="1" value="1"/> LatePass(es).
                    Student currently has <?php echo $latepasses; ?> latepasses.
                </span>
            </div>

            <div class="col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10 padding-top-one-pt-five-em padding-left-ten">
                <span class="floatleft">
                    <input type="checkbox" name="waivereqscore"/>
                </span>
                 <span class="col-md-11 col-sm-11">
                     Waive "show based on an another assessment" requirements, if applicable.
                </span>
            </div>

            <div class="col-md-offset-2 col-sm-offset-2 col-md-4 col-sm-4 padding-top-one-pt-five-em padding-left-ten">
                <input type=submit value="<?php echo $savetitle; ?>">
            </div>
        </form>
    <?php } ?>
   </div>
<?php } ?>
