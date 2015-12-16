<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Messages',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;

$address = AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id.'&filtercid=');
$imasroot = AppUtility::getHomeURL();
$saveTagged = AppUtility::getURLFromHome('message', 'message/save-tagged?cid='.$course->id);?>


<div>
    <?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>

        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $userId ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } else {?>
        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $userId ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } ?>
</div>
<input type="hidden" class="is-important" value="<?php echo $isImportant ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>

        <?php if($userRights->rights > AppConstant::GUEST_RIGHT){?>
        <div class="pull-left header-btn hide-hover">
            <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>"
            class="btn btn-primary1 pull-right  btn-color"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/newzmessg.png">&nbsp;Send New Message</a>
        </div>
        <?php } ?>
    </div>
</div>
<div class="item-detail-content">
    <?php if($userRights->rights == 100 || $userRights->rights == 20) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]);
    } elseif($userRights->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course]);
    }?>
</div>
<div class="tab-content shadowBox">
    <div class="second-level-message-navigation height-ninety">
        <div class="col-md-12 display-inline-block">
            <span class="col-sm-3 message-second-level display-inline-block padding-left-right-zero padding-top-twelve">
                 <a  id="limit-to-tag-link" href="index?cid=<?php echo $course->id; ?>&show=1">Limit to Tagged</a>
                 <a  id="show-all-link" href="index?cid=<?php echo $course->id; ?>">Show All</a>
                 <a class="padding-left-zero display-inline-block" id="sent-message"  href="<?php echo AppUtility::getURLFromHome('message', 'message/sent-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>">Sent Messages</a>
            </span>
    <!--         <input type="button"  id='imgtab' class="btn btn-primary" value="Pictures" onclick="rotatepics()" >-->
            <div class="col-md-3 display-inline-block padding-left-right-zero padding-top-five padding-left-ten">
            <span class="pull-left message-second-level" >With Selected :</span>
                <span class="with-selected-dropdown">
                    <select  class="form-control with-selected display-inline-block width-fifty-five-per width-one-thirty">
                        <option value="-1" id="Select">Select</option>
                         <option value="0" id="mark-as-unread">Mark as Unread</option>
                        <option value="1" id="mark-read">Mark as Read</option>
                        <option value="2" id="mark-delete">Delete</option>
                    </select>
                </span>
            </div>
            <div class="col-md-3 display-inline-block padding-left-right-zero padding-top-five padding-left-twenty-five">
                <div class="">
                    <span class=" pull-left message-second-level">Filter By Course :</span>
                    <span class="" >
                        <select class="show-course form-control display-inline-block width-fifty-five-per width-one-thirty" id="filtercid" onclick="chgfilter()" >
                            <?php
                                    echo "<option value=\"0\" ";
                                    if ($filtercid==0) {
                                        echo "selected=1 ";
                                    }
                                    echo ">All courses</option>";

                                    foreach($filterByCourse as $key=>$row) {
                                        echo "<option value=\"{$row['id']}\" ";
                                        if ($filtercid==$row['id']) {
                                            echo 'selected=1';
                                        }
                                        echo " >{$row['name']}</option>";
                                    }
                                    echo "</select> ";
                                ?>
                        </select>
                    </span>
                </div>
            </div>
            <div class="col-md-3 display-inline-block padding-right-zero padding-top-five">
                <div class="floatright">
                <span class="pull-left message-second-level floatleft" >By Sender :</span>
                 <span class="floatleft">
                     <select class="show-users form-control width-one-thirty" id="filteruid" onclick="chgfilter()">
                         <option value="0" ';
                        <?php if ($filteruid==0) {
                         echo 'selected="selected" ';
                         }
                         echo '>All</option>';

                        foreach($filterByUserName as $key => $row) {
                         echo "<option value=\"{$row['id']}\" ";
                         if ($filteruid==$row['id']) {
                         echo 'selected=1';
                         }
                         echo " >{$row['LastName']}, {$row['FirstName']}</option>";
                         } ?>
                     </select>
                 </span>
                 </div>
            </div>
        </div>
   </div>
    <div class="message-div">
        <table class="display table table-bordered table-striped table-hover data-table">
            <thead>
            <tr><th><div class='checkbox override-hidden'><label><input type='checkbox' id='message-header-checkbox' name='header-checked' value=''>
                            <span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></th><th>Message</th><th>Replied</th><th></th><th>Flag</th><th>From</th><th>Course</th><th>Sent</th></tr>
            </thead>
            <tbody class="message-table-body">
            <?php
            if (count($messageDisplay)==0) {
                echo "<tr><td></td><td>No messages</td><td></td></tr>";
            }
           foreach($messageDisplay as $key=>$line) {
                if (trim($line['title'])=='') {
                    $line['title'] = '[No Subject]';
                }
                $n   = 0;
                while (strpos($line['title'],'Re: ')===0) {
                    $line['title'] = substr($line['title'],4);
                    $n++;
                }
                if ($n==1) {
                    $line['title'] = 'Re: '.$line['title'];
                } else if ($n>1) {
                    $line['title'] = "Re<sup>$n</sup>: ".$line['title'];
                }
                echo "<tr id=\"tr{$line['id']}\" ";
                if (($line['isread']&8)==8) {
                    echo 'class="tagged" ';
                }
                echo "><td><input type=checkbox name=\"msg-check\" id='Checkbox' class='message-checkbox' value=\"{$line['id']}\"/></td><td>";
                echo "<a href=\"view-message?page$page&cid=$course->id&filtercid=$filtercid&filteruid=$filteruid&type=msg&msgid={$line['id']}\">";
                if (($line['isread']&1)==0) {
                    echo "<b>{$line['title']}</b>";
                } else {
                    echo $line['title'];
                }
                echo "</a></td><td>";
                if ($line['replied']==1) {
                    echo "Yes";
                }
                if ($line['LastName']==null) {
                    $line['LastName'] = "[Deleted]";
                }
                echo '</td><td>';

                if ($line['hasuserimg']==1) {
                    if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                        echo " <img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\"  class=\"userpic\"  />";
                    } else {
                        echo " <img src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\" class=\"userpic\"  />";
                    }
                }

                echo "</td><td>";
                if (($line['isread']&8)==8) {
                    echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
                } else {
                    echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
                }
                echo '</td>';
                echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";


                if ($line['name']==null) {
                    $line['name'] = "[Deleted]";
                }
                echo "<td>{$line['name']}</td>";
                $senddate = AppUtility::tzdate("F j, Y, g:i a",$line['senddate']);
                echo "<td>$senddate</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
    function chgfilter() {
        var filtercid = document.getElementById("filtercid").value;
        var filteruid = document.getElementById("filteruid").value;

        window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
    }

    var AHAHsaveurl = "<?php $saveTagged;?>"

</script>
<style type="text/css"> tr.tagged {background-color: #dff;}</style>