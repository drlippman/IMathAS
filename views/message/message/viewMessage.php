<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('View Message',false );
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div>
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
    <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <input type="hidden" class="msg-id" value="<?php echo $messages['id'] ?>">
    <?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>
<!--        Condition for toolbar-->
    <?php } else {?>
<?php } ?>
    <div class="item-detail-header">
            <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>

    </div>
    <div class="title-container padding-bottom-two-em">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page page-title-back-arrow"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
<div class="item-detail-content">

</div>
<div class="tab-content shadowBox ">
    <br>
     <div class="view-message-inner-contents min-height-three-hundred-five view-message-margin">
         <div class="row">

                 <div class="col-md-6 col-sm-6 message-title">
                     <h4 class="margin-top-zero"><b><?php echo 'Subject '?> </b><?php echo $messages->title ?></h4>
                  </div>
                 <div class="pull right col-md-6 col-sm-6 message-title ">
                     <?php echo $senddate?>
                 </div>
         </div>
         <?php
         if ($messageData['hasuserimg']==1) {
             if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                 echo " <img style=\"float:left;\" src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/Uploads/userimg_sm{$messageData['msgfrom']}.jpg\"  onclick=\"togglepic(this)\" /><br/>";
             } else {
                 $uploads = AppConstant::UPLOAD_DIRECTORY;
                  ?>
                 <img style="float:left;" class="circular-profile-image Align-link-post padding-five" src="<?php echo AppUtility::getHomeURL().$uploads.$messageData['msgfrom'].".jpg"?>" onclick="togglepic(this)" ">
              <?php }
         }
         ?>
         <div class="second-level-div row">
                 <span class="padding-top-five padding-left-one-em padding-right-pt-five-em">
                     From: <?php echo ucfirst($messageData['LastName']) . ' ' . ucfirst($messageData['FirstName']) ?>
                 </span>

             <?php
             if ($messageData['section']!='') {
                 echo ' <span class="small">(Section: '.$messageData['section'].')</span>';
             }

             if (isset($teacherof[$messageData['courseid']])) {?>
             <span class="text-deco-none padding-right-fifteen">
                    <a class="btn1 reply-button white-color" href="mailto:<?php echo $messageData['email']; ?>"><?php echo AppUtility::t('email');?></a>
             </span>
                 <span class="text-deco-none">
                    <a class="btn1 reply-button" href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$messageData['id']); ?>"><?php echo AppUtility::t('gradebook');?></a>
                 <span>

               <?php
               }?>
         </div>
        <div class="col-md-12 col-sm-12 message-body word-break-break-all" style="padding-left: 80px">
        <?php  if (($parent = strpos($messages['message'],'<hr'))!==false)
             {$messages['message'] = substr($messages['message'],0,$parent).'<a href="#" class="small" onclick="showtrimmedcontent(this);return false;">['.AppUtility::t('Show trimmed content', false).']</a><div id="trimmed" style="display:none;">'.substr($messages['message'],$parent).'</div>';
                   } ?>
           <?php echo $messages->message ?>
         </div>
         <div class="reply message-body col-md-12 col-sm-12 padding-left-zero">
         <?php if ($type!='sent' && $type!='allstu'){
             if ($cansendmsgs) {?>
                 <span class="padding-left-fifteen padding-right-fifteen text-deco-none">
                         <a href="<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $messages->id.'&cid='.$course->id); ?>"
                            class="btn1 reply-button"> <i class="fa fa-reply"></i>&nbsp;&nbsp;<?php echo AppUtility::t('Reply')?></a>
                    </span>
            <?php } ?>

             <span class="padding-right-fifteen text-deco-none">
                    <a class="btn1 btn-bg-color white-color" href="#" id="mark-delete"><?php echo AppUtility::t('Delete Message')?></a>
                 </span>
             <span class="padding-right-fifteen text-deco-none">
                     <a class="btn1 btn-bg-color" href="#" id="mark-as-unread"><?php echo AppUtility::t('Mark As Unread ')?></a>
                 </span>

             <span class="text-deco-none">
                 <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-conversation?id=' . $messages->id . '&message=' . $sent . '&baseid=' . $messages->baseid.'&cid='.$course->id); ?>" class="btn1  reply-button "><i class="fa fa-twitch"></i>&nbsp;&nbsp;<?php echo AppUtility::t('View Conversation')?></a>
            </span>
             <?php
             if($isTeacher && $messageData['courseid'] == $cid) {
             ?>
                 <span class="text-deco-none">
                    <a class="btn1 reply-button" href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$messageData['id']); ?>"><?php echo AppUtility::t('gradebook');?></a>
                 <span>
            <?php }
         } else if ($type=='sent' && $type!='allstu') {?>
                <span class="text-deco-none">
                 <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-conversation?id=' . $messages->id . '&message=' . $sent . '&baseid=' . $messages->baseid.'&cid='.$course->id); ?>" class="btn1  reply-button "><i class="fa fa-twitch"></i>&nbsp;&nbsp;<?php echo AppUtility::t('View Conversation')?></a>
            </span>
         <?php } ?>
        </div>
        </div>
    <br>
 </div>
