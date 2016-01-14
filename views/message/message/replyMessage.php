<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t(' Reply Message',false);
$this->params['breadcrumbs'][] = $this->title;
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
    <?php if($userRights->rights >= AppConstant::STUDENT_RIGHT) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]);
    }?>
</div>
<input type="hidden" class="send-msg" value="<?php echo $messages->courseid ?>">
<input type="hidden" class="msg-receiver" value="<?php echo $messages->msgto ?>">
<input type="hidden" class="msg-sender" value="<?php echo $messages->msgfrom ?>">
<input type="hidden" class="base-id" value="<?php echo $messages->baseid ?>">
<input type="hidden" class="parent-id" value="<?php echo $messages->id ?>">
<input type="hidden" class="is-replied" value="<?php echo $messages->replied ?>"
<input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
<input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<?php if ($messages->message) {
    $message = $messages->message;
    $message = '<p> </p><br/><hr/>In reply to:<br/>'.$message;
} else {
    $message = '';
}?>
<div class="tab-content shadowBox col-md-12 col-sm-12 padding-left-zero padding-right-zero">
    <div class="inner-reply-msg-content padding-top-thirty col-md-12 col-sm-12">
        <div class="drop-down padding-top col-sm-12 col-md-12">
            <div class="col-md-1 col-sm-1"><?php echo AppUtility::t('To');?></div>
            <div class="col-md-11 col-sm-11"><?php echo ucfirst($fromUser->LastName) . ' ' . ucfirst($fromUser->FirstName); ?>&nbsp;&nbsp;

                <?php if($userRights['rights'] == AppConstant::ADMIN_RIGHT) { ?>
                <span class="text-deco-none padding-right-fifteen">
                    <a class="btn1 reply-button" href="#"><?php echo AppUtility::t('email');?></a>
                </span>
                <span class="text-deco-none padding-right-ten">
                    <a class="btn1 reply-button" href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$fromUser['id']); ?>"><?php echo AppUtility::t('gradebook');?></a>
                </span>
                <?php } ?>
               <?php echo AppUtility::t('Last Login:'." ".AppUtility::tzdate("F j, Y, g:i a",$fromUser->lastaccess));?>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 padding-top">
            <div class="col-md-1 col-sm-1">
                <?php echo AppUtility::t('Subject');?>
            </div>
            <div class="col-md-11 col-sm-11">
                <input class="textbox subject form-control" id="subject" type="text" value="Re: <?php echo $messages->title ?>" onchange="changeSubject()">
            </div>
            <span id="subject-error" class="error-message subject-error-message col-md-10 col-sm-10 col-md-offset-1 col-sm-offset-1"></span>
        </div>
        <div class="col-md-12 col-sm-12 padding-top">
            <div class="col-md-1 col-sm-1"><?php echo AppUtility::t('Message');?></div>
            <div class='left col-md-11 col-sm-11'>
                <div class='editor reply-message-textarea'>
                    <textarea class="col-md-12 col-sm-12 max-width-hundred-per" id='message' name='message' rows='12' cols='15'>
                        <?php echo $message ?>
                    </textarea>
                </div>
            </div>
        </div>
        <div class="checkbox override-hidden col-md-12 col-sm-12">
            <label class="col-md-6 col-sm-6 margin-left-message">
                <input type="checkbox" class="header-checked" name="header-checked" value="1">
                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                <?php echo AppUtility::t('Mark original message unread');?>
            </label>
        </div>
    <div class="header-btn hide-hover col-md-6 col-sm-6 col-sm-offset-1 padding-left-twenty-eight padding-top-twenty-five padding-bottom-thirty">
        <a href="#" id="msg-btn" class="btn btn-primary1 btn-color"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/newzmessg.png">&nbsp;Send Message</a>
    </div>
    </div>
</div>
