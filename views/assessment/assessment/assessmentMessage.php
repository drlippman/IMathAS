<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = 'End of Assessment Messages';
$useeditor = "commonmsg"; ?>

<div class="item-detail-header">
    <?php   if(!isset($params['checked'])){ ?>
                 <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Add/Remove Question'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id.'&aid='.$params['aid'] ,AppUtility::getHomeURL().'question/question/add-questions?cid='.$course->id.'&aid='.$params['aid']] ,'page_title' => $this->title]); ?>
    <?php  }else{ ?>
                 <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
   <?php } ?>
</div>
<form id="qform" method=post action="assessment-message?cid=<?php echo $course->id ?>&record=true">
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?> </div>
            </div>
        </div>
    </div>
<div class="tab-content shadowBox non-nav-tab-item end-assesment-shadowbox  col-md-12 col-sm-12">
            <?php
                echo '<div class="col-md-12 col-sm-12 padding-left-zero padding-bottom-one-em">
                <div class="col-md-2 col-sm-3 padding-left-zero">Base messages on: </div>';
                    echo '<div class="col-md-2 col-sm-2"><input type="radio" name="type" value="0" ';
                    if ($endmsg['type']==0) { echo 'checked="checked"';}
                    echo ' />&nbsp;&nbsp;Points </div> <div class="col-md-2 col-sm-2"><input type="radio" name="type" value="1" ';
                    if ($endmsg['type']==1) { echo 'checked="checked"';}
                    echo ' />&nbsp;&nbsp;Percents</div></div>';
                    echo '<table class="assesment-msg-table col-md-12 col-sm-12"><thead><tr><th class="col-md-3 col-sm-3">If score is at least</th><th class="col-md-9 col-sm-9">Display this message</th></tr></thead><tbody>';
                    $i=1;
                    foreach($endmsg['msgs'] as $sc=>$msg) {
                        $msg = str_replace('"','&quot;',$msg);
                        echo "<tr><td><input  type=\"text\" size=\"4\" class='col-md-3 col-sm-3 form-control' name=\"sc[$i]\" value=\"$sc\"/></td>";
                        echo "<td class='padding-left-fifteen'><input type=\"text\" size=\"80\" class='col-md-9 col-sm-9 form-control' name=\"msg[$i]\" value=\"$msg\" /></td></tr>";
                        $i++;
                    }
                    for ($j=0;$j<10;$j++) {
                        echo "<tr><td><input type=\"text\" size=\"4\" class='col-md-3 col-sm-3 form-control' name=\"sc[$i]\" value=\"\"/></td>";
                        echo "<td class='padding-left-fifteen'><input type=\"text\" size=\"80\" class='col-md-9 col-sm-9 form-control' name=\"msg[$i]\" value=\"\" /></td></tr>";
                        $i++;
                    }
                    echo "<tr><td>Otherwise, show:</td>";
                    echo "<td class='padding-left-fifteen'><input type=\"text\" size=\"80\" class='col-md-12 col-sm-12 form-control' name=\"msg[0]\" value=\"{$endmsg['def']}\" /></td></tr>";
                    echo '</tbody></table>';

                    echo '<p class="col-md-12 col-sm-12 padding-left-zero">After the score-specific message, display this text to everyone</p>';
                    echo '<div class="editor col-md-12 col-sm-12 assessment-message-textarea padding-left-zero padding-right-zero">
                    <textarea cols="50" rows="10" name="commonmsg" style="width: 100%">';
                    echo htmlentities($endmsg['commonmsg']);
                    echo '</textarea>
                    </div>';?>
            <div class="header-btn col-sm-4 padding-left-zero padding-top-twenty padding-bottom-five">
                <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Save' ?></button>
            </div>
        </form>

        <p class="col-sm-12 padding-left-zero">Order of entries is not important; the message with highest applicable score will be reported.
            The "otherwise, show" message will display if no other score messages are defined.  Use this instead
            of trying to create a 0 score entry</p>
<?php    if (isset($params['checked'])) {
    echo '<input type="hidden" name="aidlist" value="'.$params['checked'].'" />';
    } else {
    echo '<input type="hidden" name="aid" value="'.$params['aid'].'" />';
    } ?>
</div>
