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
?>
<form method=post action="add-link?cid=<?php echo $course['id'];?>" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><i class="fa fa-question help-icon"></i></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $checkboxesValues['saveButtonTitle']; ?></button>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="name-of-item">
            <div class="col-lg-2"><?php AppUtility::t('Name of Link')?></div>
            <div class="col-lg-10">
                <input class="input-item-title" type=text size=0 name=name value="<?php echo str_replace('"', '&quot;', $checkboxesValues['title']); ?>">
            </div>
        </div>
     <BR>
        <div class="editor-summary" style="padding-top: 20px">
            <div class="col-lg-2">
                <?php AppUtility::t('Summary')?>
            </div>
            <?php $summary = $checkboxesValues['summary'];?>
            <?php echo "<div class='col-lg-10'>
                <div class= 'editor'>
                    <textarea cols=5 rows=12 id=summary name=summary style='width: 100%'>$summary</textarea>
                </div>
                </div><br>"; ?>
        </div><BR class=form><br>
        <div>
            <div class=col-lg-2><?php AppUtility::t('Link type')?> </div>
                <div class="col-lg-4">
                    <select id="linktype" class="form-control" name="linktype" onchange="linktypeupdate(this)">
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
                </div>
        </div><BR class=form>

    <div id="textinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_ZERO) ? "block" : "none"; ?>"><br>
        <div class="col-lg-2"><?php AppUtility::t('Text')?></div>
        <?php $text = $checkboxesValues['text'];?>
        <div class="col-lg-10">
            <div class=editor>
                <textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo htmlentities($line['text']); ?><?php echo $text ?></textarea>
            </div>
        </div>
    </div><BR class=form>

    <div id="webinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <div class="col-lg-2"><?php AppUtility::t('Weblink (start with http://)')?></div>
    			<div class="col-lg-10">
    				<input size="80" name="web" value="<?php echo htmlentities($checkboxesValues['webaddr']); ?>"/>
    			</div><br class="form">
    </div>
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_TWO) ? "block" : "none"; ?>">
        <div class="col-lg-2"><?php AppUtility::t('File')?></div>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
    			<div class="col-lg-10">

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
    			</div><br class="form">
    </div>

   <div id="toolinput""
    <div id="fileinput" style="display:<?php echo ($a['avail'] == AppConstant::NUMERIC_THREE) ? "block" : "none"; ?>">
        <div class="col-lg-2"><?php AppUtility::t('External Tool')?></div>
    			<div class="col-lg-4">
    		<?php
                $selectedtool = array();
                array_push($selectedtool,$toollabels);
                if (count($toolvals) > 0) {
                    AssessmentUtility::writeHtmlSelect('tool', $toolvals, $toollabels, $selectedtool);
                    echo '<br/>Custom parameters: <input type="text" name="toolcustom" size="40" value="' . htmlentities($toolcustom) . '" /><br/><br>';
                    echo 'Custom launch URL: <input type="text" name="toolcustomurl" size="40" value="' . htmlentities($toolcustomurl) . '" /><br/>';
                } else {
                    echo 'No Tools defined yet<br/><br>';
                }
                if (!isset($CFG['GEN']['noInstrExternalTools'])) {
                    echo '<a href="../admin/externaltools.php?cid=' . $cid . '&amp;ltfrom=' . $_GET['id'] . '">Add or edit an external tool</a>';
                }
                ?>
    			</div><br class="form"/><br>
        <div class="col-lg-2"><?php AppUtility::t('If this tool returns scores, do you want to record them?')?></div>
    			<div class="col-lg-10">
                    <input type=radio name="usegbscore" value="0" <?php if ($checkboxesValues['points'] == 0) {  echo 'checked=1'; } ?> onclick="toggleGBdetail(false)"/><span class="padding-left"><?php AppUtility::t('No')?></span><br>
                    <input type=radio name="usegbscore" value="1" <?php if ($checkboxesValues['points'] > 0) { echo 'checked=1';} ?> onclick="toggleGBdetail(true)"/><span class="padding-left"><?php AppUtility::t('Yes')?></span>
    			</div><br class="form"/><br>

        <div id="gbdetail" <?php if ($checkboxesValues['points'] == 0) {
            echo 'style="display:none;"';
        } ?>>
            <div class="col-lg-2"><?php AppUtility::t('Points')?></div>
    			<div class="col-lg-10">
    				<input type=text size=4 name="points" value="<?php echo $checkboxesValues['points'];?>"/><span class="padding-left"><?php AppUtility::t('points')?></span>
                </div><br class="form"/><br>

            <div class=col-lg-2><?php AppUtility::t('Gradebook Category')?></div>
                    <div class=col-lg-4>
                        <?php AssessmentUtility::writeHtmlSelect("gbcat",$gbcatsId,$gbcatsLabel,$valuesOfcheckBoxes['gbcat'],"Default",0); ?>
                    </div><br class=form><br>

            <div class=col-lg-2><?php AppUtility::t('Count')?> </div>
			<div class="col-lg-10">
                <input type=radio name="cntingb" value="1"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],1,0); ?>/><span class="padding-left"><?php AppUtility::t('Count in Gradebook')?></span><br>
                <input type=radio name="cntingb" value="0"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],0,0); ?>/><span class="padding-left"><?php AppUtility::t("Don't count in grade total and hide from students")?></span><br>
                <input type=radio name="cntingb" value="3"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],3,0); ?>/><span class="padding-left"><?php AppUtility::t("Don't count in grade total")?></span><br>
                <input type=radio name="cntingb" value="2"<?php AssessmentUtility::writeHtmlChecked($checkboxesValues['cntingb'],2,0); ?>/><span class="padding-left"><?php AppUtility::t("Count as Extra Credit")?></span>
			</div><br class=form><br>

            <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
            $page_tutorSelect['val'] = array(2,0,1); ?>

            <div class="col-lg-2"><?php AppUtility::t('Tutor Access')?></div>
				<div class="col-lg-4">
                    <?php
                    AssessmentUtility::writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$checkboxesValues['tutoredit']);
                    echo '<input type="hidden" name="gradesecret" value="'.$checkboxesValues['gradesecret'].'"/>';
                    ?>
			</div><br class="form" />
        </div>
    </div><br class="form"/>
        <div class=col-lg-2><?php AppUtility::t('Open page in')?> </div>
            <div class=col-lg-10>
                <input type=radio name="open-page-in" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  /><span class="padding-left"><?php AppUtility::t("Current window/tab")?></span><br>
                <input type=radio name="open-page-in" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'], AppConstant::NUMERIC_ONE); ?>  /><span class="padding-left"><?php AppUtility::t("New window/tab")?></span>
            </div><br class="form"/><br class="form"/>

        <div class=col-lg-2><?php AppUtility::t('Visibility')?></div>
            <div class=col-lg-10>
                <input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_ONE); ?>onclick="document.getElementById('datedivwithcalendar').style.display='block';document.getElementById('datediv').style.display='none';"/><span class="padding-left"><?php AppUtility::t("Show by Dates")?></span>
                <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_ZERO); ?> onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='none';"/><span class="padding-left"><?php AppUtility::t("Hide")?></span></label>
                <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail"  value="2" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_TWO); ?> onclick="document.getElementById('datedivwithcalendar').style.display='none';document.getElementById('datediv').style.display='block';"/><span class="padding-left"><?php AppUtility::t("Show Always")?></span>
            </div><br class="form"/><br>

    <div id="datedivwithcalendar"
        style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <div class=col-lg-2><?php AppUtility::t('Available After')?></div>

			<div class=col-lg-10>
                <input type=radio name="available-after" class="pull-left" value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['startDate'], "0", AppConstant::NUMERIC_ZERO); ?>/><span class="pull-left padding-left"><?php AppUtility::t("Always until end date")?></span>
                <label class="pull-left non-bold" style="padding-left: 40px"><input type=radio name="available-after" class="pull-left" value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['startDate'], "1", AppConstant::NUMERIC_ONE); ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-lg-4">';
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
                    echo '<label class="end pull-left non-bold"> at </label>';
                    echo '<div class="pull-left col-lg-4">';

                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $sTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
                </div>
        	<BR class=form><br>

        <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
		  <div class=col-lg-10>
              <label class="pull-left non-bold"><input type=radio name="available-until" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($endDate, "2000000000", 0); ?>/><span class="padding-left"><?php AppUtility::t("Always after start date")?></span></label>
              <label class="pull-left" style="padding-left: 32px"><input type=radio name="available-until" class="pull-left" value="1"  <?php AssessmentUtility::writeHtmlChecked($endDate, "2000000000", 1); ?>/></label>
                <?php
                echo '<div class = "time-input pull-left col-lg-4">';
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
                echo '<label class="end pull-left non-bold"> at </label>';
                echo '<div class="pull-left col-lg-4">';

                echo TimePicker::widget([
                    'name' => 'etime',
                    'value' => $eTime,
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                echo '</div>'; ?>
              </div>
		  <BR class=form>

        <div class=col-lg-2><?php AppUtility::t('Place on Calendar?')?></div>
		 <div class=col-lg-10>
             <input type=radio name="place-on-calendar" value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['altoncal'],AppConstant::NUMERIC_ZERO); ?>  /><span class="padding-left"><?php AppUtility::t('No')?></span><br>
             <input type=radio name="place-on-calendar" value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['altoncal'], AppConstant::NUMERIC_ONE); ?>  /><span class="padding-left"><?php AppUtility::t('Yes, on Available after date (will only show after that date)')?></span><br>
             <input type=radio name="place-on-calendar"  value="2" <?php AssessmentUtility::writeHtmlChecked($forumData['altoncal'], AppConstant::NUMERIC_ZERO); ?>  /><span class="padding-left"><?php AppUtility::t(' Yes, on Available until date')?></span>
             <br><?php AppUtility::t('With tag')?><span class="padding-left"><input type="text" size="3" value="!" name="tag"></span>
         </div><br class="form"/><br>
    </div>
    <div id="datediv" style="display:<?php echo ($assessmentData['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <div class=col-lg-2><?php AppUtility::t('Place on Calendar?')?></div>
		<div class=col-lg-10>
            <input type=radio name="place-on-calendar" value="0" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'],AppConstant::NUMERIC_ZERO); ?>  /><span class="padding-left"><?php AppUtility::t('No')?></span><br>
            <input type=radio name="place-on-calendar" value="1" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'], AppConstant::NUMERIC_ONE); ?>  /><span class="padding-left"><?php AppUtility::t('Yes, on Available after date (will only show after that date)')?></span><br>
            <input type=radio name="place-on-calendar" value="2" <?php AssessmentUtility::writeHtmlChecked($checkboxesValues['altoncal'] , AppConstant::NUMERIC_ZERO); ?>  /><span class="padding-left"><?php AppUtility::t('Yes, on Available until date')?></span>
            <br><?php AppUtility::t('With tag')?><span class="padding-left"><input type="text" size="3" value="!" name="tag"></span>
        </div><br class="form"/><br>
    </div>

    <?php if (count($pageOutcomesList) > 0) { ?>
    <div class="col-lg-2"><?php AppUtility::t('Associate Outcomes')?></div><div class="col-lg-10">
    <?php
        $gradeoutcomes = array();
        AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
    <br class="form"/>
    <?php } ?>
    </div><br class="form"/>
    <br>
    <br>
    </div>
</form>

