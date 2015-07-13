<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = 'Wiki';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$hidetitle = false;
?>
    <form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
        <span class=form>Name: </span>
    <span class=formright>
        <?php $title = 'Enter wiki name here';
        if($wiki['name']){
            $title = $wiki['name'];
        } ?>
        <input type=text size=60 name=name value="<?php echo $title;?>"></span>
        <BR class=form>

        Description:<BR>
        <div class=editor>
            <textarea cols=60 rows=20 id=description name=description style="width: 100%">
                <?php $text = "<p>Enter Wiki description here</p>";
                if($wiki['description'])
                {
                    $text = $wiki['description'];
                }
                echo htmlentities($text);?>
            </textarea>
        </div>

        <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($wiki['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($wiki['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($wiki['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/>Show Always<br/>
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
                    <span class=formright>
                      <select name="group-wiki" class="form-control">
                          <option value="0" selected>Not group wiki</option>
                          <?php foreach ($groupNames as $groupName) { ?>
                              <option value="<?php echo $groupName['id']; ?>" selected>Use group
                                  set:<?php echo $groupName['name']; ?></option>
                          <?php } ?>
                      </select>
        </span>
		</span><br class="form"/>

        <span class=form>Students can edit:</span>
		<span class=formright>
			<input type=radio name="rdatetype" value="0" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="rdatetype" value="2000000000" <?php if ($revisedate==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="rdatetype" class="pull-left" value="2" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/>Before:

            <?php
            echo '<div class = "col-lg-4 time-input">';
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
            echo '<label class="end col-lg-7"> at </label>';
            echo '<div class="col-lg-6">';

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

        <div class=submit><input type=submit class="btn btn-primary" value="<?php echo $saveTitle;?>"></div>
    </form>


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