<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = 'End of Assessment Messages';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = $this->title;
$useeditor = "commonmsg"; ?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<form id="qform" method=post action="assessment-message?cid=<?php echo $course->id ?>&record=true">
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?> </div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Save' ?></button>
            </div>
        </div>
    </div>
<div class="tab-content shadowBox non-nav-tab-item">
    <div class="col-lg-12">
            <?php
                echo '<p>Base messages on: ';
                    echo '<input type="radio" name="type" value="0" ';
                    if ($endmsg['type']==0) { echo 'checked="checked"';}
                    echo ' />Points <input type="radio" name="type" value="1" ';
                    if ($endmsg['type']==1) { echo 'checked="checked"';}
                    echo ' />Percents</p>';
                    echo '<table class="assesment-msg-table"><thead><tr><th>If score is at least</th><th>Display this message</th></tr></thead><tbody>';
                    $i=1;
                    foreach($endmsg['msgs'] as $sc=>$msg) {
                        $msg = str_replace('"','&quot;',$msg);
                        echo "<tr><td><input  type=\"text\" size=\"4\" class='form-control' name=\"sc[$i]\" value=\"$sc\"/></td>";
                        echo "<td><input type=\"text\" size=\"80\" class='form-control' name=\"msg[$i]\" value=\"$msg\" /></td></tr>";
                        $i++;
                    }
                    for ($j=0;$j<10;$j++) {
                        echo "<tr><td><input type=\"text\" size=\"4\" class='col-md-2 form-control' name=\"sc[$i]\" value=\"\"/></td>";
                        echo "<td><input type=\"text\" size=\"80\" class='col-md-10 form-control' name=\"msg[$i]\" value=\"\" /></td></tr>";
                        $i++;
                    }
                    echo "<tr><td>Otherwise, show:</td>";
                    echo "<td><input type=\"text\" size=\"80\" class='form-control' name=\"msg[0]\" value=\"{$endmsg['def']}\" /></td></tr>";
                    echo '</tbody></table>';

                    echo '<p>After the score-specific message, display this text to everyone:</p>';
                    echo '<div class=editor><textarea cols="50" rows="10" name="commonmsg" style="width: 100%">';
                    echo htmlentities($endmsg['commonmsg']);
                    echo '</textarea></div>';
            echo '</form>';
            ?>
            <p>Order of entries is not important; the message with highest applicable score will be reported.
            The "otherwise, show" message will display if no other score messages are defined.  Use this instead
            of trying to create a 0 score entry</p>
   </di22v>
<?php    if (isset($params['checked'])) {
    echo '<input type="hidden" name="aidlist" value="'.$params['checked'].'" />';
    } else {
    echo '<input type="hidden" name="aid" value="'.$params['aid'].'" />';
    } ?>
</div>
