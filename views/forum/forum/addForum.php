<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AssessmentUtility;

$this->title = 'Add Forum';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$hidetitle = false;
?>


<h3><b><?php echo $pageTitle; ?></b><img src="<?php echo AppUtility::getAssetURL() ?>img/help.gif" alt="Help"
                                         onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
</h3>
<?php if (isset($modifyForumId)){ ?>
<form enctype="multipart/form-data" method=post
      action="add-forum?cid=<?php echo $course->id ?>&modifyFid=<?php echo $modifyForumId; ?>">
    <?php }else{ ?>
    <form enctype="multipart/form-data" method=post action="add-forum?cid=<?php echo $course->id ?>">
        <?php } ?>

        <span class=form>Name: </span>
	<span class=formright>
        <!-- Title-->
        <?php $title = 'Enter Forum Name here';
        //        AppUtility::dump($forumData);
        if ($forumData) {
            $title = $forumData['name'];
        }
        ?>
        <input type=text size=0 name=title value="<?php echo $title; ?>"><br/>


	</span>
        <!--    Text Editor-->
        <BR class=form>
        &nbsp;&nbsp; Description:<BR>

        <div>
            <?php echo "<span class='left col-md-11'><div class= 'editor'>
            <textarea id='forum-description' name='forum-description'  style='width: 100%;' rows='20' cols='200'>";
            $text = "<p>Enter forum description here</p>";
            if ($forumData) {
                $text = $forumData['description'];
            }
            echo htmlentities($text);
            ?>
            </textarea>
        </div>
        <!--Show-->
        <div>
            <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($forumData['avail'], 0); ?>
                   onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($forumData['avail'], 1); ?>
                   onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($forumData['avail'], 2); ?>
                   onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>Show Always<br/>
		</span><br class="form"/>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">

                <span class=form>Available After:</span>
		        <span class=formright>
			        <input type=radio name="available-after"
                           value="0" <?php writeHtmlChecked($forumData['startdate'], '0', 0) ?>/>
			        Always until end date<br/>
			        <input type=radio name="available-after" class="pull-left"
                           value="sdate" <?php echo AssessmentUtility::writeHtmlChecked($forumData['startdate'], '0', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
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
                        'value' => time(),
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
                           value="2000000000" <?php echo AssessmentUtility::writeHtmlChecked($forumData['enddate'], '2000000000', 0) ?>/>
                        Always after start date<br/>
                        <input type=radio name="available-until" class="pull-left"
                               value="edate" <?php writeHtmlChecked($forumData['enddate'], '2000000000', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
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
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		    </span><BR class=form>
            </div>
            <fieldset>
                <!-- --><?php //AppUtility::dump($groupNames);?>
                <span class=form>Group forum?</span>
                    <span class=formright>
                      <select name="group-forum" class="form-control">
                          <option value="0" selected>Not group forum</option>
                          <?php foreach ($groupNames as $groupName) { ?>
                              <option value="<?php echo $groupName['id']; ?>" selected>Use group
                                  set:<?php echo $groupName['name']; ?></option>
                          <?php } ?>
                      </select>

                        <?php
                        //AssessmentUtility::writeHtmlSelect ("copyfrom",$pageCopyFromSelect['val'],$pageCopyFromSelect['label'],AppConstant::NUMERIC_ZERO,"None - use settings below",AppConstant::NUMERIC_ZERO," onChange=\"chgcopyfrom()\"");
                        ?>
		</span><br class=form>


                <span class=form>Allow anonymous posts:</span>
                    <span class=formright>
            <input type="checkbox" value="1" name="allow-anonymous-posts">
	            </span><br class=form>

                <span class=form>Allow students to modify posts:</span>
                    <span class=formright>
            <input type="checkbox" value="2" name="allow-students-to-modify-posts">
	            </span><br class=form>

                <span class=form>Allow students to delete own posts (if no replies):</span>
                    <span class=formright>
            <input type="checkbox" value="4" name="allow-students-to-delete-own-posts">
	            </span><br class=form>

                <span class=form>Turn on "liking" posts:</span>
                    <span class=formright>
            <input type="checkbox" value="8" name="like-post">
	            </span><br class=form>

                <span class=form>Viewing before posting:</span>
                    <span class=formright>
            <input type="checkbox" value="16" name="viewing-before-posting">
	            </span><br class=form>

                <span class=form>Get email notify of new posts:</span>
                    <span class=formright>
            <input type="checkbox" name="Get-email-notify-of-new-posts">
	            </span><br class=form>

                <span class=form>Default display:</span>
                    <span class=formright>

            <select name="default-display" class="form-control">
                <option value="0" selected>Expanded</option>
                <option value="2" selected>Condensed</option>
            </select>
		</span><br class=form>


                <span class=form>Sort threads by:</span>
		<span class=formright>
			<input type=radio name="sort-thread" value="0" <?php writeHtmlChecked($startdate, '0', 0) ?>>Thread start date<br/>
 			<input type=radio name="sort-thread" value="1"<?php writeHtmlChecked($startdate, '0', 1) ?>>Most recent reply date<br/>
		</span><br class="form"/>

                <span class=form>Students can create new threads:</span>
		        <span class="formright">
			<input type=radio name="new-thread"
                   value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO); ?>>Alway <br/>
			<input type=radio name="new-thread"
                   value="2000000000" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'], 2000000000, AppConstant::NUMERIC_ZERO); ?>>Never<br/>
			<input type=radio name="new-thread" class="pull-left "
                   value="1" <?php if ($assessmentData['reviewdate'] > AppConstant::NUMERIC_ZERO && $assessmentData['reviewdate'] < 2000000000) {
                echo "checked=1";
            } ?>>
                    <?php
                    echo '<label class="end pull-left">Before:</label>';
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'newThreadDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left"> at </label>';
                    echo '<div class=" col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'newThreadTime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>

		</span><BR class=form>

                <span class=form>Students can reply to posts:</span>
		        <span class="formright">
			<input type=radio name="reply-to-posts"
                   value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ZERO); ?>>Alway <br/>
			<input type=radio name="reply-to-posts"
                   value="2000000000" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'], 2000000000, AppConstant::NUMERIC_ZERO); ?>>Never<br/>
			<input type=radio name="reply-to-posts" class="pull-left "
                   value="1" <?php if ($assessmentData['reviewdate'] > AppConstant::NUMERIC_ZERO && $assessmentData['reviewdate'] < 2000000000) {
                echo "checked=1";
            } ?>>
                    <?php
                    echo '<label class="end pull-left">Before:</label>';
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'replayPostDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left"> at </label>';
                    echo '<div class=" col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'replayPostTime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>

		</span><BR class=form>


                <span class=form>Calendar icon:</span>
                    <span class=formright>
            New Threads: <input type="text" name="calendar-icon-text1" value="FP" size="2"> , Replies: <input
                            type="text" name="calendar-icon-text2" value="FR" size="2">
	            </span><br class=form>


                <span class=form>Count in gradebook?</span>
		<span class=formright>
			<input type=radio name="count-in-gradebook" value="0" <?php writeHtmlChecked($forumData['avail'], 0); ?>
                   onclick="document.getElementById('count-in-gb').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>No<br/>
			<input type=radio name="count-in-gradebook" value="1" <?php writeHtmlChecked($forumData['avail'], 1); ?>
                   onclick="document.getElementById('count-in-gb').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>Yes<br/>
			<input type=radio name="count-in-gradebook" value="4" <?php writeHtmlChecked($forumData['avail'], 4); ?>
                   onclick="document.getElementById('count-in-gb').style.display='block';document.getElementById('altcaldiv').style.display='block';"/>Yes, but hide from students for now<br/>
			<input type=radio name="count-in-gradebook" value="2" <?php writeHtmlChecked($forumData['avail'], 2); ?>
                   onclick="document.getElementById('count-in-gb').style.display='block';document.getElementById('altcaldiv').style.display='block';"/>Yes, as extra credit<br/>
		</span><br class="form"/>

                <div id="count-in-gb" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">
                    <span class=form>Points:</span>
                    <span class=formright>
            <input type="text" name="points" value="0" size="3"> points
	            </span><br class=form>

                    <span class=form>Gradebook Category:</span>
                    <span class=formright>

            <select name="gradebook-category" class="form-control">
                <option value="0" selected>Default</option>
            </select>
            </span><br class=form>

                    <span class=form>Tutor Access:</span>
                    <span class=formright>

            <select name="tutor-access" class="form-control">
                <option value="2" selected>No access to scores</option>
                <option value="0" selected>View Scores</option>
                <option value="1" selected>View and Edit Scores</option>
            </select>
</span><br class=form>


                    <span class=form>Use Scoring Rubric</span>
                    <span class=formright>
                        <select name="rubric" class="form-control">
                            <option value="0" selected>None</option>
                            <?php foreach ($rubricsData as $single) { ?>
                                <option
                                    value="<?php echo $single['id'] ?>"><?php echo $single['name']; ?></option>
                            <?php } ?>
                        </select>

</span>
                            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid=' . $course->id) ?>">Add new
                            rubric</a> | <a
                            href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/edit-rubric?cid=' . $course->id) ?>">Edit
                            rubrics</a>


<br class=form>

                    <span class="form">Associate Outcomes:</span>
			<span class="formright">
                <select name="associate-outcomes" class="form-control">
                    <?php
                    $inGroup = false;
                    $isSelected = false;
                    foreach ($pageOutcomesList as $outcome) {
                        if ($outcome[1]==AppConstant::NUMERIC_ONE) {//is group
                            if ($inGroup) { echo '</optgroup>';}
                            echo '<optgroup label="'.htmlentities($outcome[0]).'">';
                            $inGroup = true;
                        } else {
                            echo '<option value="'.$outcome[0].'" ';
                            if ($assessmentData['defoutcome'] == $outcome[0]) { echo 'selected="selected"'; $isSelected = true;}
                            echo '>'.$pageOutcomes[$outcome[0]].'</option>';
                        }
                    }
                    if ($inGroup) { echo '</optgroup>';}
                    ?>
                </select>
			</span>
                        <input type="button" value="Add Another">

<br class=form>
</div>

                        <span class=form>Forum Type:</span>
		<span class=formright>
			<input type=radio name="forum-type" value="0" <?php writeHtmlChecked($forumData['avail'], 0); ?>
                   onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>Regular forum<br/>
			<input type=radio name="forum-type" value="1" <?php writeHtmlChecked($forumData['avail'], 1); ?>
                   onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>File sharing forum<br/>
		</span><br class="form"/>
                        <span class=form>Categorize posts?</span>
                    <span class=formright>
                <input type=checkbox name="categorize-posts" value="1" <?php if ($forumData['taglist'] != '') {
                    echo "checked=1";
                } ?>
                       onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';"/>
			  <span id="tagholder" style="display:<?php echo ($line['taglist'] == '') ? "none" : "inline"; ?>">
			  Enter in format CategoryDescription:category,category,category<br/>
			  <textarea rows="2" cols="60" name="taglist"><?php echo $line['taglist']; ?></textarea>
			  </span>


            </fieldset>


            <div class=submit>
                <button type=submit name="submitbtn" class="btn btn-primary"
                        value="Create Forum"><?php echo $saveTitle; ?></button>
            </div>
        </div>
        </div>
    </form>


    <!--Functions-->
    <?php
    function writeHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                echo "checked ";
            }
        } else {
            if ($var == $test) {
                echo "checked ";
            }
        }
    }

    //writeHtmlChecked is used for checking the appropriate radio box on html forms
    function getHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                return "checked ";
            }
        } else {
            if ($var == $test) {
                return "checked ";
            }
        }
    }

    //writeHtmlSelected is used for selecting the appropriate entry in a select item
    function writeHtmlSelected($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                echo 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                echo 'selected="selected"';
            }
        }
    }

    //writeHtmlSelected is used for selecting the appropriate entry in a select item
    function getHtmlSelected($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                return 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                return 'selected="selected"';
            }
        }
    }

    ?>
