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
            <textarea id='inlineText' name='inlineText'  style='width: 100%;' rows='20' cols='200'>";
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

            <span class=form>Available After:</span>
		        <span class=formright>
			        <input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			        Always until end date<br/>
			        <input type=radio name="sdatetype" class="pull-left" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
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
		        </span><BR class=form>

            <span class=form>Available Until:</span>
		        <span class=formright>
			        <input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/>
                        Always after start date<br/>
                        <input type=radio name="edatetype" class="pull-left" value="edate" <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
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
		    </span><BR class=form>

            <!--Place on Calendar-->
            <span class=form>Place on Calendar?</span>
		        <span class=formright>
                    <input type=radio name="oncal" value=0 <?php writeHtmlChecked($inlineText['oncal'],0); ?> /> No<br/>
                    <input type=radio name="oncal" value=1 <?php writeHtmlChecked($inlineText['oncal'],1); ?> /> Yes, on Available after date (will only show after that date)<br/>
                    <input type=radio name="oncal" value=2 <?php writeHtmlChecked($inlineText['oncal'],2); ?> /> Yes, on Available until date<br/>
                    With tag: <input name="caltag" type=text size=1 value="<?php echo $inlineText['caltag'];?>"/>
                </span><br class="form" />
        </div>
        <div id="altcaldiv" style="display:<?php echo ($inlineText['avail']==2)?"block":"none"; ?>">

            <span class=form>Place on Calendar?</span>
		        <span class=formright>
                    <input type=radio name="altoncal" value="0" <?php writeHtmlChecked($altoncal,0); ?> /> No<br/>
                    <input type=radio name="altoncal" class="pull-left" value="1" <?php writeHtmlChecked($altoncal,1); ?> /> Yes, on
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
                    <br/><br>
                    With tag: <input name="altcaltag" type=text size=1 value="<?php echo $inlineText['caltag'];?>"/>
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
