<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
$this->title = 'Add Link';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = $this->title;
//echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>

<?php echo 'Add Link';?>

<form method=post action="add-link">
    <p></p>
    <span class=form>Title:</span>
    <span class=formright><input type=text size=30 name=name value="<?php echo str_replace('"','&quot;',$assessmentData['name']);?>"></span><BR class=form>
    Summary:<BR>
    <div >
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea cols=50 rows=15 id=summary name=summary style='width: 100%'>$assessmentData->summary</textarea></div></div><br>"; ?>
    </div><BR class=form>

    <span class=form>Link Type: </span>
			<span class=formright>
				<select name="displaymethod">
                    <option value="text" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"AllAtOnce",AppConstant::NUMERIC_ZERO) ?>>Page of text</option>
                    <option value="web" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"OneByOne",AppConstant::NUMERIC_ZERO) ?>>Web link</option>
                    <option value="File" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"Seq",AppConstant::NUMERIC_ZERO) ?>>File</option>
                    <option value="Tool" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"SkipAround",AppConstant::NUMERIC_ZERO) ?>>External Tool</option>
                </select>
			</span><BR class=form>

    Intro/Instructions:<BR>
    <div>
        <?php echo "<div class='left col-md-11'><div>
    <textarea cols=50 rows=20 id='intro' name='intro' style='width: 100%'>$assessmentData->intro</textarea></div></div><br>"; ?>
    </div><BR>

    <span class=form> Open page in:</span>
		<span class=formright>
			<input type=radio name="open-page-in" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'],AppConstant::NUMERIC_ZERO);?>  /> Current window/tab<br/>
			<input type=radio name="open-page-in" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'],AppConstant::NUMERIC_ONE);?>  /> New window/tab<br/>
        </span><br class="form"/>

    <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ZERO);?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ONE);?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
		<input type=radio name="avail" value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ZERO);?> onclick="document.getElementById('datediv').style.display='none';"/>Show Always<br/>
        </span><br class="form"/>

    <div id="datediv" style="display:<?php echo ($assessmentData['avail']==AppConstant::NUMERIC_ONE)?"block":"none"; ?>">

        <span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ZERO); ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ONE); ?>/>

            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'sdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $sDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
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
            echo '</div>';?>

		</span><BR class=form>

        <span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",0); ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" class="pull-left" value="edate"  <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",1); ?>/>
            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'edate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $eDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
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
            echo '</div>';?>

		</span><BR class=form>

        <span class="form">Keep open as review:</span>
		<span class="formright">
			<input type=radio name="doreview" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?>> Never<br/>
			<input type=radio name="doreview" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],2000000000,AppConstant::NUMERIC_ZERO); ?>> Always after due date<br/>
			<input type=radio name="doreview" class="pull-left " value="rdate" <?php if ($assessmentData['reviewdate']>AppConstant::NUMERIC_ZERO && $assessmentData['reviewdate']<2000000000) { echo "checked=1";} ?>>
            <?php
            echo '<label class="end pull-left"> Until</label>';
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'rdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $reviewDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
            <?php
            echo '<label class="end pull-left"> at </label>';
            echo '<div class=" col-lg-6">';
            echo TimePicker::widget([
                'name' => 'rtime',
                'value' => $reviewTime,
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>

		</span><BR class=form>





        <span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="place-on-calendar" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'],AppConstant::NUMERIC_ZERO);?>  /> No<br/>
			<input type=radio name="place-on-calendar" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'],AppConstant::NUMERIC_ONE);?>  /> Yes, on Available after date (will only show after that date)<br/>
            <input type=radio name="place-on-calendar" value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['open-page-in'],AppConstant::NUMERIC_ZERO);?>  /> Yes, on Available until date<br/>
            <br>With tag:<input type="text" size="3" value="0" name="">
        </span><br class="form"/>

    </div>

    <div class=submit><input class=""  type=submit name="" value=""></div>
    </form>