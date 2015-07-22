<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;

$this->title = $checkboxesValues['saveTitle'];
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<h3><b><?php echo $checkboxesValues['saveTitle']; ?></b>
</h3>
<form method=post action="add-link?cid=<?php echo $course['id'];?>" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
    <p></p>
    <span class=form>Title:</span>
    <span class=formright><input type=text size=30 name=name
                                 value="<?php echo str_replace('"', '&quot;', $checkboxesValues['title']); ?>"></span><BR
        class=form>
    Summary:<BR>

    <div>
 <?php $summary = $checkboxesValues['summary'];?>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea cols=50 rows=15 id=summary name=summary style='width: 100%'>$summary</textarea></div></div><br>"; ?>
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
        <?php $text = $checkboxesValues['text'];?>
        <div class=editor>
            <textarea cols=80 rows=20 id=text name=text
                      style="width: 100%"><?php echo htmlentities($line['text']); ?><?php echo $text ?></textarea>
        </div>
    </div>
    <div id="webinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class="form">Weblink (start with http://)</span>
    			<span class="formright">
    				<input size="80" name="web" value="<?php echo htmlentities($checkboxesValues['webaddr']); ?>"/>
    			</span><br class="form">
    </div>
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_TWO) ? "block" : "none"; ?>">
        <span class="form">File</span>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
    			<span class="formright">

    				<?php if ($checkboxesValues['filename'] != '') {
                    require_once("../includes/filehandler.php");
                    echo '<input type="hidden" name="curfile" value="' . $checkboxesValues['filename'] . '"/>';
                    $alink = getcoursefileurl($checkboxesValues['filename']);
                    echo 'Current file: <a href="' . $alink . '">' . basename($checkboxesValues['filename']) . '</a><br/>Replace ';
                } else {
                    echo 'Attach ';
                }
                ?>
                    file (Max 10MB)<sup>*</sup>: <input name="userfile" type="file"/>
    			</span><br class="form">
    </div>

   <div id="toolinput""
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_THREE) ? "block" : "none"; ?>">
        <span class="form">External Tool</span>
    			<span class="formright">
    		<?php
                $selectedtool = array();
                array_push($selectedtool,$toollabels);
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
    			<input type=radio name="usegbscore" value="0" <?php if ($checkboxesValues['points'] == 0) {
                    echo 'checked=1';
                } ?> onclick="toggleGBdetail(false)"/>No<br/>
    			<input type=radio name="usegbscore" value="1" <?php if ($checkboxesValues['points'] > 0) {
                    echo 'checked=1';
                } ?> onclick="toggleGBdetail(true)"/>Yes
    			</span><br class="form"/>

        <div id="gbdetail" <?php if ($checkboxesValues['points'] == 0) {
            echo 'style="display:none;"';
        } ?>>
            <span class="form">Points:</span>
    			<span class="formright">
    				<input type=text size=4 name="points" value="<?php echo $checkboxesValues['points'];?>"/> points
    			</span><br class="form"/>

            <span class=form>Gradebook Category:</span>
                    <span class=formright>
 <?php AssessmentUtility::writeHtmlSelect("gbcat",$gbcatsId,$gbcatsLabel,$valuesOfcheckBoxes['gbcat'],"Default",0); ?>
            </span><br class=form>

            <span class=form>Count: </span>
			<span class="formright">
				<input type=radio name="cntingb" value="1"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],1,0); ?>/> Count in Gradebook<br/>
				<input type=radio name="cntingb" value="0"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],0,0); ?>/> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb" value="3"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],3,0); ?>/> Don't count in grade total<br/>
				<input type=radio name="cntingb" value="2"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],2,0); ?>/> Count as Extra Credit
			</span><br class=form>
            <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
            $page_tutorSelect['val'] = array(2,0,1); ?>

            <span class="form">Tutor Access:</span>
				<span class="formright">
	<?php
    AssessmentUtility::writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$checkboxesValues['tutoredit']);
    echo '<input type="hidden" name="gradesecret" value="'.$checkboxesValues['gradesecret'].'"/>';
    ?>
			</span><br class="form" />
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
                       value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_ZERO); ?>
                       onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='none';"/>Hide<br/>
                <input type=radio name="avail"
                       value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_ONE); ?>
                       onclick="document.getElementById('datedivwithcalendar').style.display='block';document.getElementById('datediv').style.display='none';"/>Show by Dates<br/>
            <input type=radio name="avail"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_TWO); ?>
                   onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='block';"/>Show Always<br/>
            </span><br class="form"/>

    <div id="datedivwithcalendar"
        style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class=form>Available After:</span>

			<span class=formright>
                <input type=radio name="available-after"
                       value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['startDate'], "0", AppConstant::NUMERIC_ZERO); ?>/>
                Always until end date<br/>
                <input type=radio name="available-after" class="pull-left"
                       value="sdate" <?php AssessmentUtility::writeHtmlChecked($forumData['startDate'], "0", AppConstant::NUMERIC_ONE); ?>/>

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
                   value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['oncal'],AppConstant::NUMERIC_ZERO); ?>  /> No<br/>
			<input type=radio name="place-on-calendar"
                   value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['oncal'], AppConstant::NUMERIC_ONE); ?>  /> Yes, on Available after date (will only show after that date)<br/>
            <input type=radio name="place-on-calendar"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($forumData['oncal'], AppConstant::NUMERIC_ZERO); ?>  /> Yes, on Available until date<br/>
            <br>With tag:<input type="text" size="3" value="!" name="tag">
         </span><br class="form"/>
    </div>
    <div id="datediv" style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="place-on-calendar"
                   value="0" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'],AppConstant::NUMERIC_ZERO); ?>  /> No<br/>
			<input type=radio name="place-on-calendar"
                   value="1" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'], AppConstant::NUMERIC_ONE); ?>  /> Yes, on Available after date (will only show after that date)<br/>
            <input type=radio name="place-on-calendar"
                   value="2" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'] , AppConstant::NUMERIC_ZERO); ?>  /> Yes, on Available until date<br/>
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
    </span><br class="form"/>
    <div class=submit><input class="" type=submit name="submit" value="<?php echo $checkboxesValues['saveButtonTitle'];?>"></div>
</form>
