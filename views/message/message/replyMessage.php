<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t(' Reply Message',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Message:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn hide-hover">
            <a href="#" id="msg-btn" class="btn btn-primary1 pull-right  btn-color"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/newzmessg.png">&nbsp;Send Message</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($userRights->rights == 100 || $userRights->rights == 20) {
        echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($userRights->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
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
<div class="tab-content shadowBox">
    <div class="inner-reply-msg-content padding-top-one-ten">
        <div class="drop-down padding-top col-sm-12">
            <div class="col-sm-1"><?php echo AppUtility::t('To');?></div>
            <div class="col-sm-11"><?php echo ucfirst($fromUser->FirstName) . ' ' . ucfirst($fromUser->LastName); ?>&nbsp;&nbsp;<a
                    href="#"><?php echo AppUtility::t('email');?></a>&nbsp;|&nbsp;<a href="#"><?php echo AppUtility::t('gradebook');?></a>
               <?php echo AppUtility::t('Last Login:'." ".date("F j, Y, g:i a",$fromUser->lastaccess));?>
            </div>
        </div>
        <div class="col-sm-12 padding-top">
            <div class="col-sm-1"><?php echo AppUtility::t('Subject');?></div>
            <div class="col-sm-8"><input class="textbox subject" type="text" value="Re: <?php echo $messages->title ?>">
            </div>
        </div>
        <div class="col-sm-12 padding-top">
            <div class="col-sm-1"><?php echo AppUtility::t('Message');?></div>
            <?php echo "<div class='left col-sm-11'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='12' cols='15'>$message</textarea></div></div><br>"; ?>
        </div>
        <div class="checkbox override-hidden col-sm-12">
            <label class="col-sm-6 margin-left-message">
                <input type="checkbox" class="header-checked" name="header-checked" value="1">
                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                <?php echo AppUtility::t('Mark original message unread');?>
            </label>
        </div>
    </div>
</div>
