<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$hidetitle = false;
include_once('../components/filehandler.php');
?>
<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><i class="fa fa-question help-icon"></i></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share" style="padding-right: 10px"></i><?php echo $saveTitle ?></button>
            </div>
        </div>
    </div>

<div class="tab-content shadowBox" style="margin-top:30px">
    <div style="padding-top: 20px">
        <div class="col-lg-2"><?php AppUtility::t('Name of Inline Text')?></div>
        <div class="col-lg-10">
            <?php $title = 'Enter title here';
            if($inlineText['title']){
                $title = $inlineText['title'];
            } ?>
            <input type=text size=0 name=title style="width: 100%;height: 40px; border: #6d6d6d 1px solid;" value="<?php echo $title;?>">
            <input type="checkbox" name="hidetitle" value="1" <?php writeHtmlChecked($hidetitle,true) ?>/><?php AppUtility::t('Hide title and icon')?>
        </div>
    </div>
    <BR>
    <div style="margin-top: 20px">
        <div class="col-lg-2"><?php AppUtility::t('Summary')?></div>
        <div class="col-lg-10">
            <div class=editor>
                <textarea cols=5 rows=12 id=description name=description style="width: 100%;">
                    <?php $text = AppUtility::t('Enter text here');
                    if($inlineText['text'])
                    {
                        $text = $inlineText['text'];
                    }
                    echo htmlentities($text);?>
                </textarea>
            </div>
        </div>
    </div>
    <!--File Attachment -->
	<div class=col-lg-2><?php AppUtility::t('Attached Files')?></div>
	<div class=col-lg-10>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
		    New file<sup>*</sup>: <input type="file" name="userfile" /> <br/>
		    Description: <input type="text" name="newfiledescr"/><br/>
		<input type=submit name="submitbtn" class="btn btn-primary" value="Add / Update Files"/>
	</div>
    <br class=form>

    <!--List of Youtube Videos-->
    <div>
        <div class="col-lg-2"><?php AppUtility::t('List of YouTube videos')?></div>
        <div class="col-lg-10">
            <div class="checkbox"><label class="inline-checkbox" style="padding-left: 0"><input type="checkbox" name="isplaylist" value="1" <?php writeHtmlChecked($inlineText['isplaylist'],1);?>/><span class="cr"><i class="cr-icon fa fa-check"></i></span></label><td><?php AppUtility::t('Show as embedded playlist')?></td></div>
        </div>
    </div>
    <br class="form"/>

    <!--Show-->
        <div class="col-lg-2"><?php AppUtility::t('Visibility')?></div>
        <div class="col-lg-10">
            <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="avail" value="0" <?php writeHtmlChecked($inlineText['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Hide')?></td></div>
            <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="avail" value="1" <?php writeHtmlChecked($inlineText['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show by Dates')?></td></div>
            <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="avail" value="2" <?php writeHtmlChecked($inlineText['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Always')?></td></div>
        </div>
		<br class="form"/>

    <!--Show by dates-->
    <!--Show by dates-->
    <div id="datediv" style="display:<?php echo ($inlineText['avail']==1)?"block":"none"; ?>">

        <div class=col-lg-2><?php AppUtility::t('Available After')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t(' Always until end date')?></td></div>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="sdatetype" class="pull-left" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td></div>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'EventDate',
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
                        'name' => 'end_time',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
		        </div><BR class=form>

                <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always after start date')?></td></div>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td> <input type=radio name="edatetype" class="pull-left" value="edate" <?php writeHtmlChecked($enddate,'2000000000',1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td></div>

                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'EventDate',
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
                        'name' => 'end_time',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
		    </div><BR class=form>

        <!--Place on Calendar-->
        <div class=col-lg-2><?php AppUtility::t('Place on Calendar?')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="oncal" value=0 <?php writeHtmlChecked($inlineText['oncal'],0); ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('No')?></td></div>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="oncal" value=1 <?php writeHtmlChecked($inlineText['oncal'],1); ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Yes, on Available after date (will only show after that date)')?></td></div>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="oncal" value=2 <?php writeHtmlChecked($inlineText['oncal'],2); ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Yes, on Available until date')?></td></div>
                    With tag: <input name="caltag" type=text size=1 value="<?php echo $inlineText['caltag'];?>"/>
                </div><br class="form" />
    </div>
    <div id="altcaldiv" style="display:<?php echo ($inlineText['avail']==2)?"block":"none"; ?>">

        <div class=col-lg-2><?php AppUtility::t('Place on Calendar?')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility' style="padding-left: 0"><td><input type=radio name="altoncal" value="0" <?php writeHtmlChecked($altoncal,0); ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('No')?></td></div>
                    <div class='radio student-enroll visibility pull-left'><label class='checkbox-size label-visibility pull-left' style="padding-left: 0"><td><input type=radio name="altoncal" class="pull-left" value="1" <?php writeHtmlChecked($altoncal,1); ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Yes On')?></td>
                    <?php
                    echo '<div class = "col-lg-9 time-input pull-right">';
                    echo DatePicker::widget([
                        'name' => 'EventDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    echo '</div>';?>
                        </div>
                    With tag: <input name="altcaltag" type=text size=1 value="<?php echo $inlineText['caltag'];?>"/>
                </div><BR class=form>
    </div>
</form>
<br>
<br>

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