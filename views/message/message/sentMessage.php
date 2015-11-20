<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Sent Message ',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Message:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" class="send-course-id" value="<?php echo $course->id ?>">
<input type="hidden" class="send-user-id" value="<?php echo $course->ownerid ?>">
<div class="tab-content shadowBox">
    <div class="col-sm-12 second-level-message-navigation">
        <div class=" col-sm-2 align-links-message">
            <a  href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>"><?php echo AppUtility::t('Received Messages')?></a>
        </div>
        <div class=" col-sm-3 align-links-dropdwn">
            <span class="select-text-margin pull-left"><?php echo AppUtility::t('Filter By Courses')?></span>
        <span class="col-sm-4 with-selected-dropdown">
            <select name="seluid" class="show-course form-control-message" id="course-sent-id">
                <option value="0"><?php echo AppUtility::t('All Courses')?></option>
            </select></span>

        </div>
        <div class="col-sm-3 align-links-dropdwn">
            <span class="select-text-margin pull-left"><?php echo AppUtility::t('By Recipient')?></span>
        <span class="col-sm-4 with-selected-dropdown">
        <select name="seluid" class="show-users form-control-message" id="user-sent-id">
            <option value="0"><?php echo AppUtility::t('Select a user')?></option>
        </select>
        </span>
        </div>
        <div class="col-sm-3 align-links-dropdwn">
            <span class="pull-left message-second-level" ><?php echo AppUtility::t('With Selected')?></span>
            <span class="col-sm-4 with-selected-dropdown">
                <select  class="form-control-message with-selected" >
                    <option value="0"><?php echo AppUtility::t('Select')?></option>
                    <option value="1" id="mark-sent-delete"><?php echo AppUtility::t('Remove From Sent Message List')?></option>
                    <option value="2" id="mark-unsend"><?php echo AppUtility::t('Unsend')?></option>
                </select>
            </span>
        </div>

    </div>
    <div class="message-div"></div>
</div>

<script type="text/javascript" src="<?php echo AppUtility::getHomeURL()?>js/message/sentMessage.js" ></script>