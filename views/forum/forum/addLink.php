<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;

$this->title = 'Add Link';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<h3><b><?php echo 'Add Link'; ?></b>
</h3>

<form method=post action="add-link?cid=<?php echo $course['id'];?>" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
    <p></p>
    <span class=form>Title:</span>
    <span class=formright><input type=text size=30 name=name
                                 value="<?php echo str_replace('"', '&quot;', $assessmentData['name']); ?>"></span><BR
        class=form>
    Summary:<BR>

    <div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea cols=50 rows=15 id=summary name=summary style='width: 100%'>$assessmentData->summary</textarea></div></div><br>"; ?>
    </div>
    <BR class=form>
    <BR class=form>
    <span class=form>Link type: </span>
    		<span class="formright">
    		<select id="linktype" name="linktype" onchange="linktypeupdate(this)">
                <option value="text"<?php AssessmentUtility::writeHtmlChecked($a, AppConstant::NUMERIC_ZERO); ?>
                        onclick="document.getElementById('textinput').style.display='block';">Page of text
                </option>
                <option value="web"<?php AssessmentUtility::writeHtmlChecked($a, AppConstant::NUMERIC_ONE); ?>
                        onclick="document.getElementById('webinput').style.display='block';">Web link
                </option>
                <option value="file"<?php AssessmentUtility::writeHtmlChecked($a, AppConstant::NUMERIC_TWO); ?>
                        onclick="document.getElementById('fileinput').style.display='block';">File
                </option>
                <option value="tool"<?php AssessmentUtility::writeHtmlChecked($a, AppConstant::NUMERIC_THREE); ?>
                        onclick="document.getElementById('toolinput').style.display='block';">External Tool
                </option>
            </select>
    		</span><br class="form"/>


    <div id="textinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_ZERO) ? "block" : "none"; ?>">
        Text<BR>

        <div class=editor>
            <textarea cols=80 rows=20 id=text name=text
                      style="width: 100%"><?php echo htmlentities($line['text']); ?></textarea>
        </div>
    </div>
    <div id="webinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class="form">Weblink (start with http://)</span>
    			<span class="formright">
    				<input size="80" name="web" value="<?php echo htmlentities($webaddr); ?>"/>
    			</span><br class="form">
    </div>
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_TWO) ? "block" : "none"; ?>">
        <span class="form">File</span>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
    			<span class="formright">
    			<?php if ($filename != '') {
                    require_once("../includes/filehandler.php");
                    echo '<input type="hidden" name="curfile" value="' . $filename . '"/>';
                    $alink = getcoursefileurl($filename);
                    echo 'Current file: <a href="' . $alink . '">' . basename($filename) . '</a><br/>Replace ';
                } else {
                    echo 'Attach ';
                }
                ?>
                    file (Max 10MB)<sup>*</sup>: <input name="userfile" type="file"/>
    			</span><br class="form">
    </div>
    <div id="toolinput"
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_THREE) ? "block" : "none"; ?>">
        <span class="form">External Tool</span>
    			<span class="formright">
    			<?php
                $selectedtool = array();
                array_push($selectedtool,$toollabels);
//                AppUtility::dump($selectedtool);
                if (count($toolvals) > 0) {
                    AssessmentUtility::writeHtmlSelect('tool', $toolvals, $toollabels, $selectedtool);
                    echo '<br/>Custom parameters: <input type="text" name="toolcustom" size="40" value="' . htmlentities($toolcustom) . '" /><br/>';
                    echo 'Custom launch URL: <input type="text" name="toolcustomurl" size="40" value="' . htmlentities($toolcustomurl) . '" /><br/>';
                } else {
                    echo 'No Tools defined yet<br/>';
                }
                if (!isset($CFG['GEN']['noInstrExternalTools'])) {
                    echo '<a href="../admin/externaltools.php?cid=' . $cid . '&amp;ltfrom=' . $_GET['id'] . '">Add or edit an external tool</a>';
                }
                ?>
    			</span><br class="form"/>
        <span class="form">If this tool returns scores, do you want to record them?</span>
    			<span class="formright">
    			<input type=radio name="usegbscore" value="0" <?php if ($points == 0) {
                    echo 'checked=1';
                } ?> onclick="toggleGBdetail(false)"/>No<br/>
    			<input type=radio name="usegbscore" value="1" <?php if ($points > 0) {
                    echo 'checked=1';
                } ?> onclick="toggleGBdetail(true)"/>Yes
    			</span><br class="form"/>

        <div id="gbdetail" <?php if ($points == 0) {
            echo 'style="display:none;"';
        } ?>>
            <span class="form">Points:</span>
    			<span class="formright">
    				<input type=text size=4 name="points" value="0"/> points
    			</span><br class="form"/>


            <span class=form>Gradebook Category:</span>
                    <span class=formright>

            <select name="gradebook-category" class="form-control">
                <option value="0" selected>Default</option>
            </select>
            </span><br class=form>


            <span class=form>Count: </span>
			<span class="formright">
				<input type=radio name="cntingb" value="1"/> Count in Gradebook<br/>
				<input type=radio name="cntingb" value="0"/> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb" value="3"/> Don't count in grade total<br/>
				<input type=radio name="cntingb" value="2"/> Count as Extra Credit
			</span><br class=form>

            <span class=form>Tutor Access:</span>
            <span class=formright>
                <select name="tutor-access" class="form-control">
                    <option value="2" selected>No access to scores</option>
                    <option value="0" selected>View Scores</option>
                    <option value="1" selected>View and Edit Scores</option>
                </select>
            </span><br class=form>
        </div>
    </div>
        <span class=form> Open page in:</span>
            <span class=formright>
                <input type=radio name="open-page-in"
                       value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /> Current window/tab<br/>
                <input type=radio name="open-page-in"
                       value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ONE); ?>  /> New window/tab<br/>
            </span><br class="form"/>

        <span class=form>Show:</span>
            <span class=formright>
                <input type=radio name="avail"
                       value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'], AppConstant::NUMERIC_ZERO); ?>
                       onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='none';"/>Hide<br/>
                <input type=radio name="avail"
                       value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'], AppConstant::NUMERIC_ONE); ?>
                       onclick="document.getElementById('datedivwithcalendar').style.display='block';document.getElementById('datediv').style.display='none';"/>Show by Dates<br/>
            <input type=radio name="avail"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'], AppConstant::NUMERIC_TWO); ?>
                   onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='block';"/>Show Always<br/>
            </span><br class="form"/>

    <div id="datedivwithcalendar"
        style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class=form>Available After:</span>

			<span class=formright>
                <input type=radio name="available-after"
                       value="0" <?php AssessmentUtility::writeHtmlChecked($startDate, "0", AppConstant::NUMERIC_ZERO); ?>/>
                Always until end date<br/>
                <input type=radio name="available-after" class="pull-left"
                       value="sdate" <?php AssessmentUtility::writeHtmlChecked($startDate, "0", AppConstant::NUMERIC_ONE); ?>/>

                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $sDate,
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';

                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $sTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>

        	</span><BR class=form>

        <span class=form>Available Until:</span>
		  <span class=formright>
                <input type=radio name="available-until"
                       value="2000000000" <?php AssessmentUtility::writeHtmlChecked($endDate, "2000000000", 0); ?>/>
                 Always after start date<br/>
                <input type=radio name="available-until" class="pull-left"
                       value="edate"  <?php AssessmentUtility::writeHtmlChecked($endDate, "2000000000", 1); ?>/>
                <?php
                echo '<div class = "pull-left col-lg-4 time-input">';
                echo DatePicker::widget([
                    'name' => 'edate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $eDate,
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>'; ?>
                <?php
                echo '<label class="end pull-left col-lg-1"> at </label>';
                echo '<div class="pull-left col-lg-6">';

                echo TimePicker::widget([
                    'name' => 'etime',
                    'value' => $eTime,
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                echo '</div>'; ?>

		  </span><BR class=form>

        <span class=form>Place on Calendar?</span>
		 <span class=formright>
			<input type=radio name="place-on-calendar"
                   value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /> No<br/>
			<input type=radio name="place-on-calendar"
                   value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ONE); ?>  /> Yes, on Available after date (will only show after that date)<br/>
            <input type=radio name="place-on-calendar"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /> Yes, on Available until date<br/>
            <br>With tag:<input type="text" size="3" value="!" name="tag">
         </span><br class="form"/>
    </div>
    <div id="datediv" style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="place-on-calendar"
                   value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /> No<br/>
			<input type=radio name="place-on-calendar"
                   value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ONE); ?>  /> Yes, on Available after date (will only show after that date)<br/>
            <input type=radio name="place-on-calendar"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /> Yes, on Available until date<br/>
            <br>With tag:<input type="text" size="3" value="!" name="tag">
        </span><br class="form"/>
    </div>

    <?php if (count($pageOutcomesList) > 0) { ?>
    <span class="form">Associate Outcomes:</span></span class="formright">
    <?php
        $gradeoutcomes = array();
        AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
    <br class="form"/>
    <?php } ?>

    <div class=submit><input class="" type=submit name="submit" value="Create Link"></div>
</form>

<script>
    function linktypeupdate(el) {
        var tochg = ["text", "web", "file", "tool"];
        for (var i = 0; i < 4; i++) {
            if (tochg[i] == el.value) {
                disp = "";
            } else {
                disp = "none";
            }
            document.getElementById(tochg[i] + "input").style.display = disp;
        }
    }
    function toggleGBdetail(v) {
        document.getElementById("gbdetail").style.display = v ? "block" : "none";
    }

</script>