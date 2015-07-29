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
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share"></i><?php echo $saveTitle ?></button>
            </div>
        </div>
    </div>

    <div class="tab-content shadowBox" style="margin-top:30px">
        <div style="padding-top: 20px">
            <div class="col-lg-2">Name of Wiki</div>
            <div class="col-lg-10">
                <?php if($wiki['name']){
                      $title = $wiki['name'];
                    } ?>
                <input type=text size=0 style="width: 100%;height: 40px; border: #6d6d6d 1px solid;" name=name value="<?php echo $title;?>">
            </div>
        </div>
        <BR class=form>

        <div style="margin-top: 20px">
            <div class="col-lg-2">Description</div>
            <div class="col-lg-10">
                <div class=editor>
                    <textarea cols=5 rows=12 id=description name=description style="width: 100%">
                        <?php $text = "<p>Enter Wiki description here</p>";
                        if($wiki['description'])
                        {
                            $text = $wiki['description'];
                        }
                        echo htmlentities($text);?>
                    </textarea>
                </div>
            </div>
        </div>

        <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/>Show Always<br/>
		</span><br class="form"/>

        <div id="datediv" style="display:<?php echo ($wiki['avail']==1)?"block":"none"; ?>">
            <span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" class="pull-left" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" class="pull-left" value="sdate" <?php  writeHtmlChecked($startdate,'0',1) ?>/>
            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'StartDate',
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
                'name' => 'start_end_time',
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
			<input type=radio name="edatetype" class="pull-left" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" class="pull-left" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'EndDate',
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
                'name' => 'end_end_time',
                'value' => time(),
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>
		</span><BR class=form>
        </div>
        <span class=form>Group wiki?</span><span class=formright>
<?php
if ($started) {
    writeHtmlSelect("ignoregroupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0,$started?'disabled="disabled"':'');
    echo '<input type="hidden" name="groupsetid" value="'.$line['groupsetid'].'" />';
} else {
    writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0);
}
?>
		</span><br class="form"/>

        <span class=form>Students can edit:</span>
		<span class=formright>
			<input type=radio name="rdatetype" value="Always" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="rdatetype" value="Never" <?php if ($revisedate==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="rdatetype" value="Date" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/>Before:

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
		</span><br class="form" />
    </form>
</div>
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
function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
    echo "<select name=\"$name\" id=\"$name\" ";
    echo (isset($actions)) ? $actions : "" ;
    echo ">\n";
    if (isset($defaultLabel) && isset($defaultVal)) {
        echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
    }
    for ($i=0;$i<count($valList);$i++) {
        if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
            echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
        } else {
            echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
        }
    }
    echo "</select>\n";
}

?>