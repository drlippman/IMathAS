<?php
use app\components\AppUtility;
use yii\helpers\Html;
use app\components\AppConstant;
$this->title = AppUtility::t('Modify Question Settings', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" class="" value="<?php echo $courseId = $course->id?>">
<form method=post action="mod-question?process=true&<?php echo "cid=$courseId&aid=$assessmentId";
    if (isset($params['id'])) {echo "&id={$params['id']}";} if (isset($params['qsetid'])) {echo "&qsetid={$params['qsetid']}";}?>">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Add/Remove Question'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id.'&aid='.$params['aid'] ,AppUtility::getHomeURL().'question/question/add-questions?cid='.$course->id.'&aid='.$params['aid']] ,'page_title' => $this->title]); ?>
    </div>
    <!--Course name-->
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php AppUtility::t('Modify Question Settings') ?></div>
            </div>
            <div class="pull-left header-btn">
                <div class="submit floatright"><input type="submit" value="Save Settings"></div>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox margin-top-fourty">
    <div class="col-md-12 mod-question-form">
        <?php
        if ($overwriteBody==1) {
        echo $body;
        } else {
        ?>
        <?php echo $pageBeenTakenMsg; ?>
        <div class="col-md-12 text-label"><div class="col-md-6"><h4>Leave items blank to use the assessment's default values</h4></div></div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Points for this problem:</div><div class="col-md-4"> <input type=text size=4 name=points class="form-control" value="<?php echo $line['points'];?>"></div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Attempts allowed for this problem &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (0 for unlimited):</div><div class="col-md-4"> <input type=text class="form-control" size=4 name=attempts value="<?php echo $line['attempts'];?>"></div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Default penalty:</div>
            <div class="col-md-1"><input type=text class="form-control" size=4 name=penalty value="<?php echo $line['penalty'];?>"></div><span class="pull-left">%</span>
            <div class="col-md-3">
                <select name="skippenalty" class="form-control modify-question-drop-down" <?php if ($taken) {echo 'disabled=disabled';}?>>
                    <option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
                    <option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
                    <option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
                    <option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
                    <option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
                    <option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
                    <option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
                    <option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">New version on reattempt?</div><div class="col-md-4">
                <select name="regen" class="form-control">
                    <option value="0" <?php if (($line['regen']%3)==0) { echo 'selected="1"';}?>>Use Default</option>
                    <option value="1" <?php if (($line['regen']%3)==1) { echo 'selected="1"';}?>>Yes, new version on reattempt</option>
                    <option value="2" <?php if (($line['regen']%3)==2) { echo 'selected="1"';}?>>No, same version on reattempt</option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Allow &quot;Try similar problem&quot;?</div>
            <div class="col-md-4">
                <select name="allowregen" class="form-control">
                    <option value="0" <?php if ($line['regen']<3) { echo 'selected="1"';}?>>Use Default</option>
                    <option value="1" <?php if ($line['regen']>=3) { echo 'selected="1"';}?>>No</option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Show Answers</div><div class="col-md-4">
                <select name="showans" class="form-control">
                    <option value="0" <?php if ($line['showans']==='0') { echo 'selected="1"';}?>>Use Default</option>
                    <option value="N" <?php if ($line['showans']==='N') { echo 'selected="1"';}?>>Never during assessment</option>
                    <option value="F" <?php if ($line['showans']==='F') { echo 'selected="1"';}?>>Show answer after last attempt</option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Show hints and video/text buttons?</div>
            <div class="col-md-4">
                <select name="showhints" class="form-control">
                    <option value="0" <?php if ($line['showhints']==0) { echo 'selected="1"';}?>>Use Default</option>
                    <option value="1" <?php if ($line['showhints']==1) { echo 'selected="1"';}?>>No</option>
                    <option value="2" <?php if ($line['showhints']==2) { echo 'selected="1"';}?>>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <div class="col-md-3">Use Scoring Rubric</div>
            <div class="col-md-2">
                <?php
                AppUtility::writeHtmlSelect('rubric',$rubricVals,$rubricNames,$line['rubric']);
                ?>
                </div><div class="modify-question-drop-down">
                <?php
                echo " <a href=".AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$courseId.'&id=new&from=modq&aid='.$assessmentId.'&qid='.$params['id']).">Add new rubric</a> ";
                echo "| <a href=".AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$courseId.'from=modq&aid='.$assessmentId,'&qid='.$params['id']).">Edit rubrics</a> ";
                ?>
            </div>
        </div>
        <div class="col-md-12 text-label">
            <?php
            if (isset($params['qsetid'])) { //adding new question
                echo "<div class=col-md-3>Number of copies of question to add:</div><div class=col-md-4><input type=text class=form-control size=4 name=copies value=\"1\"/></div>";
            } else if (!$beentaken) {
                echo "<div class=col-md-3>Number, if any, of additional copies to add to assessment:</div><div class=col-md-4><input type=text class=form-control size=4 name=copies value=\"0\"/></div>";
            } ?>
        </div>
            <?php
            if ($beentaken) {
                echo '<div class="col-md-12 text-label">';
                echo '<div class="form"><a href="#" onclick="$(this).hide();$(\'#advanced\').show();return false">Advanced</a></div>';
                echo '<div id="advanced" style="display:none;">';
                echo '<span class="form">Replace this question with question ID: <br/>';
                echo '<span style="color:red">WARNING: This is NOT recommended. It will mess up the question for any student who has already attempted it, and any work they have done may look garbled when you view it</span></span>';
                echo '<span class="formright"><input size="7" name="replacementid"/></span><br class="form"/>';
                echo '</div>';
                echo '</div>';
            } ?>
        <?php } ?>
        </div>
    </div>