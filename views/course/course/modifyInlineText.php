<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = 'Modify InlineText';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$hidetitle = false;
include_once('../components/filehandler.php');
?>


<h3><b><?php echo $pageTitle; ?></b><img src="<?php echo AppUtility::getAssetURL() ?>img/help.gif"  alt="Help" onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></h3>
<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
    <span class=form>Title: </span>
	<span class=formright>
        <!-- Title-->
         <?php $title = 'Enter title here';
            if($inlineText['title']){
                $title = $inlineText['title'];
         } ?>
        <input type=text size=0 name=title value="<?php echo $title ;?>"><br/>

		<input type="checkbox" name="hidetitle" value="1" <?php writeHtmlChecked($hidetitle,true) ?>/>Hide title and icon
	</span>
        <!--    Text Editor-->
    <BR class=form>
    Text:<BR>
    <div>
        <?php echo "<span class='left col-md-11'><div class= 'editor'>
            <textarea id='inlineText' name='text'  style='width: 100%;' rows='20' cols='200'>";
                $text = "<p>Enter text here</p>";
                if($inlineText['text'])
                {
                    $text = $inlineText['text'];
                }
               echo htmlentities($text);?>
            </textarea>
    </div>

    <!--File Attachment -->
	<span class=form>Attached Files:</span>
	<span class=wideformright>

        <a href="<?php echo getcoursefileurl($arr['link']); ?>" target="_blank">
            View</a>
		<input type="text" name="filedescr-<?php echo $arr['fid'] ?>" value="<?php echo $arr['desc'] ?>"/>
		Delete? <input type=checkbox name="delfile-<?php echo $arr['fid'] ?>"/><br/>

        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
		    New file<sup>*</sup>: <input type="file" name="userfile" /> <br/>
		    Description: <input type="text" name="newfiledescr"/><br/>
		<input type=submit name="submitbtn" class="btn btn-primary" value="Add / Update Files"/>
	</span>
    <br class=form>

    <!--List of Youtube Videos-->
    <span class="form">List of YouTube videos</span>
	<span class="formright">
		<input type="checkbox" name="isplaylist" value="1" <?php writeHtmlChecked($inlineText['isplaylist'],1);?>/> Show as embedded playlist
	</span>
    <br class="form"/>

    <!--Show-->
    <div>
        <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($inlineText['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($inlineText['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($inlineText['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>Show Always<br/>
		</span><br class="form"/>

    <!--Show by dates-->
        <div id="datediv" style="display:<?php echo ($inlineText['avail']==1)?"block":"none"; ?>">
            <?php $startTime = $eTime;?>
            <span class=form>Available After:</span>
		        <span class=formright>
			        <input type=radio name="available-after" value="0" <?php writeHtmlChecked($defaultValue['startDate'], '0', AppConstant::NUMERIC_ZERO) ?>/>
			        Always until end date<br/>
			        <input type=radio name="available-after" class="pull-left" value="1" <?php writeHtmlChecked($defaultValue['startDate'] , '1', AppConstant::NUMERIC_ONE) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m-d-Y"),
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
                        'value' => $defaultValue['sTime'],
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		        </span><BR class=form>

            <span class=form>Available Until:</span>
		        <span class=formright>
			        <input type=radio name="available-until" value="2000000000" <?php writeHtmlChecked($defaultValue['endDate'], '2000000000', 0) ?>/>
                        Always after start date<br/>
                        <input type=radio name="available-until" class="pull-left" value="1" <?php writeHtmlChecked($defaultValue['endDate'], '2000000000', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m-d-Y"),
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
                        'value' => $defaultValue['eTime'],
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		    </span><BR class=form>

            <!--Place on Calendar-->
            <span class=form>Place on Calendar?</span>
		        <span class=formright>
                    <input type=radio name="place-on-calendar" value=0 <?php writeHtmlChecked($inlineText['oncal'],AppConstant::NUMERIC_ZERO); ?> /> No<br/>
                    <input type=radio name="place-on-calendar" value=1 <?php writeHtmlChecked($inlineText['oncal'],AppConstant::NUMERIC_ONE); ?> /> Yes, on Available after date (will only show after that date)<br/>
                    <input type=radio name="place-on-calendar" value=2 <?php writeHtmlChecked($inlineText['oncal'],AppConstant::NUMERIC_TWO); ?> /> Yes, on Available until date<br/>
                    With tag: <input name="caltag" type=text size=3 value="!"/>
                </span><br class="form" />
        </div>
        <div id="altcaldiv" style="display:<?php echo ($inlineText['avail']==2)?"block":"none"; ?>">

            <span class=form>Place on Calendar?</span>
		        <span class=formright>
                    <input type=radio name="place-on-calendar" value="0" <?php writeHtmlChecked($inlineText['altoncal'],AppConstant::NUMERIC_ZERO); ?> /> No<br/>
                    <input type=radio name="place-on-calendar" class="pull-left" value="1" <?php writeHtmlChecked($inlineText['altoncal'],AppConstant::NUMERIC_ONE); ?> /> Yes, on
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'Calendar',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
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
                        'name' => 'calendar_end_time',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                    <br/><br>
                    With tag: <input name="tag" type=text size=3 value="!"/>
                </span><BR class=form>
        </div>
    </div>
    <div class=submit><button type=submit name="submitbtn" class="btn btn-primary" value="Submit"><?php echo $saveTitle ?></button></div>
</form>

<!--Functions-->
<?php
function writeHtmlChecked ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            echo "checked ";
        }
    } else {
        if ($var==$test) {
            echo "checked ";
        }
    }
}

//writeHtmlChecked is used for checking the appropriate radio box on html forms
function getHtmlChecked ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            return "checked ";
        }
    } else {
        if ($var==$test) {
            return "checked ";
        }
    }
}

//writeHtmlSelected is used for selecting the appropriate entry in a select item
function writeHtmlSelected ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            echo 'selected="selected"';
        }
    } else {
        if ($var==$test) {
            echo 'selected="selected"';
        }
    }
}

//writeHtmlSelected is used for selecting the appropriate entry in a select item
function getHtmlSelected ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            return 'selected="selected"';
        }
    } else {
        if ($var==$test) {
            return 'selected="selected"';
        }
    }
}
?>
<script>

</script>