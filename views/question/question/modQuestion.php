<?php
use app\components\AppUtility;

if ($overwriteBody==1) {
echo $body;
} else {
?>
<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div>
<?php echo $pageBeenTakenMsg; ?>


<div id="headermodquestion" class="pagetitle"><h2>Modify Question Settings</h2></div>
<form method=post action="mod-question?process=true&<?php echo "cid=$courseId&aid=$assessmentId";
if (isset($params['id'])) {echo "&id={$params['id']}";} if (isset($params['qsetid'])) {echo "&qsetid={$params['qsetid']}";}?>">
    Leave items blank to use the assessment's default values<br/>

    <span class=form>Points for this problem:</span><span class=formright> <input type=text size=4 name=points value="<?php echo $line['points'];?>"></span><BR class=form>

    <span class=form>Attempts allowed for this problem (0 for unlimited):</span><span class=formright> <input type=text size=4 name=attempts value="<?php echo $line['attempts'];?>"></span><BR class=form>

    <span class=form>Default penalty:</span><span class=formright><input type=text size=4 name=penalty value="<?php echo $line['penalty'];?>">%
   <select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
       <option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
       <option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
       <option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
       <option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
       <option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
       <option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
       <option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
       <option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
   </select></span><BR class=form>

    <span class=form>New version on reattempt?</span><span class=formright>
    <select name="regen">
        <option value="0" <?php if (($line['regen']%3)==0) { echo 'selected="1"';}?>>Use Default</option>
        <option value="1" <?php if (($line['regen']%3)==1) { echo 'selected="1"';}?>>Yes, new version on reattempt</option>
        <option value="2" <?php if (($line['regen']%3)==2) { echo 'selected="1"';}?>>No, same version on reattempt</option>
    </select></span><br class="form"/>

    <span class="form">Allow &quot;Try similar problem&quot;?</span>
<span class=formright>
    <select name="allowregen">
        <option value="0" <?php if ($line['regen']<3) { echo 'selected="1"';}?>>Use Default</option>
        <option value="1" <?php if ($line['regen']>=3) { echo 'selected="1"';}?>>No</option>
    </select></span><br class="form"/>

    <span class=form>Show Answers</span><span class=formright>
    <select name="showans">
        <option value="0" <?php if ($line['showans']=='0') { echo 'selected="1"';}?>>Use Default</option>
        <option value="N" <?php if ($line['showans']=='N') { echo 'selected="1"';}?>>Never during assessment</option>
        <option value="F" <?php if ($line['showans']=='F') { echo 'selected="1"';}?>>Show answer after last attempt</option>
    </select></span><br class="form"/>

    <span class=form>Show hints and video/text buttons?</span><span class=formright>
    <select name="showhints">
        <option value="0" <?php if ($line['showhints']==0) { echo 'selected="1"';}?>>Use Default</option>
        <option value="1" <?php if ($line['showhints']==1) { echo 'selected="1"';}?>>No</option>
        <option value="2" <?php if ($line['showhints']==2) { echo 'selected="1"';}?>>Yes</option>
    </select></span><br class="form"/>

    <span class=form>Use Scoring Rubric</span><span class=formright>
<?php
AppUtility::writeHtmlSelect('rubric',$rubricVals,$rubricNames,$line['rubric']);
echo " <a href=".AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$courseId.'&id=new&from=modq&aid='.$assessmentId.'&qid='.$params['id']).">Add new rubric</a> ";
echo "| <a href=".AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$courseId.'from=modq&aid='.$assessmentId,'&qid='.$params['id']).">Edit rubrics</a> ";
?>
    </span><br class="form"/>
<?php
if (isset($params['qsetid'])) { //adding new question
    echo "<span class=form>Number of copies of question to add:</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
} else if (!$beentaken) {
    echo "<span class=form>Number, if any, of additional copies to add to assessment:</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
}

if ($beentaken) {
    echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'#advanced\').show();return false">Advanced</a></span><br class="form"/>';
    echo '<div id="advanced" style="display:none;">';
    echo '<span class="form">Replace this question with question ID: <br/>';
    echo '<span style="color:red">WARNING: This is NOT recommended. It will mess up the question for any student who has already attempted it, and any work they have done may look garbled when you view it</span></span>';
    echo '<span class="formright"><input size="7" name="replacementid"/></span><br class="form"/>';
    echo '</div>';
}

echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';
}

?>