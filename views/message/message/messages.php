<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Messages',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
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
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id]]); ?>
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
                        <select name="seluid" class="show-course form-control display-inline-block width-fifty-five-per width-one-thirty" id="course-id">
                            <option value="0">All Courses</option>
                        </select>
                    </span>
                </div>
            </div>
            <div class="col-md-3 display-inline-block padding-right-zero padding-top-five">
                <div class="floatright">
                <span class="pull-left message-second-level floatleft" id="by-Sender">By Sender :</span>
                 <span class="floatleft">
                     <select name="seluid" class="show-users form-control width-one-thirty" id="user-id">
                        <option value="0">All Users</option>
                     </select>
                 </span>
                 </div>
            </div>
        </div>
   </div>
    <div class="message-div">
    </div>
</div>
