<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div>
    <?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>

        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } else {?>
        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } ?>
</div>
<input type="hidden" class="is-important" value="<?php echo $isImportant ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithButton",['item_name'=>'Message', 'link_title'=>'Home', 'link_url' => AppUtility::getHomeURL().'site/index', 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox">
    <div class="second-level-message-navigation col-lg-12">

        <!--                <a href="--><?php //echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?><!--"-->
        <!--                   class="btn btn-primary">Send New Message</a>-->
            <span class="pull left col-sm-3 message-second-level">
                 <a  id="limit-to-tag-link" href="index?cid=<?php echo $course->id; ?>&show=1">Limit to Tagged</a>
                 <a  id="show-all-link" href="index?cid=<?php echo $course->id; ?>">Show All</a>
                 <a id="sent-message"  href="<?php echo AppUtility::getURLFromHome('message', 'message/sent-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>">Sent Messages</a>
            </span>
           <span class="col-sm-2 with-selectxed-dropdown">
                <select  class="form-control with-selected" >
                    <option value="-1" id="mark-as-unread">With Selected</option>
                     <option value="0" id="mark-as-unread">Mark as Unread</option>
                    <option value="1" id="mark-read">Mark as Read</option>
                    <option value="2" id="mark-delete">Delete</option>
                </select>
            </span>
            <span class=" pull-left message-second-level">Filter By Course :</span>
            <span class=" col-sm-2" >
                <select name="seluid" class="show-course form-control" id="course-id">
                    <option value="0">All Courses</option>
                </select>
            </span>
             <span class=" pull-left message-second-level" id="by-Sender">By Sender :</span>
             <span class="col-sm-2">
                 <select name="seluid" class="show-users form-control" id="user-id">
                    <option value="0">All Users</option>
                 </select>
             </span>
        <div class="col-lg-0">
            <input type="button"  id='imgtab' class="btn1 reply-button" value="Pictures" onclick="rotatepics()" >
        </div>

   </div>

    <div class="message-div">
        <table id='message-table display-message-table' class='message-table display-message-table table table-bordered table-striped table-hover data-table'>
            <thead><tr><th><div class='checkbox'><label><input type='checkbox' name='header-checked' value=''><span class='cr'><i class='cr-icon fa fa-check'></i></span></label>   </div></th><th>Message</th><th>Sent</th><th>Course</th><th>Replied</th><th>Action</th>
                </tr></thead><tbody class='message-table-body'></tbody></table>
    </div>
</div>