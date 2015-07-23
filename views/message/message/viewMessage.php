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
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
    <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>
<!--        Condition for toolbar-->
    <?php } else {?>

<?php } ?>
</div>
<input type="hidden" class="msg-id" value="<?php echo $messages['id'] ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithButton",['item_name'=>'Message', 'link_title'=>'Home', 'link_url' => AppUtility::getHomeURL().'site/index', 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">

</div>
<div class="tab-content shadowBox ">
    <br>
     <div class="view-message-inner-contents">
         <div class="row">

                 <div class=" col-sm-6 message-title">
                     <h4><?php echo $messages->title ?></h4>
                  </div>
                 <div class="pull right col-sm-6 message-title ">
                     <?php echo date('M d, o g:i a', $messages->senddate) ?>
                 </div>
         </div>
         <div class="second-level-div row">
             <div class="col-sm-2">
                 <span>From: <?php echo ucfirst($fromUser->FirstName) . ' ' . ucfirst($fromUser->LastName) ?></span>
             </div>
             <div class=" pull right col-sm-8">
                 <a  id="mark-delete"> Delete Message</a>&nbsp;
             </div>
             <div class="col-sm-2 pull right">
                <a id="mark-as-unread"> Mark Unread </a>&nbsp;
             </div>
         </div>

         <br>
         <div class="col-sm-12">

                <?php  if (($parent = strpos($messages['message'],'<hr'))!==false)
             {$messages['message'] = substr($messages['message'],0,$parent).'<a href="#" class="small" onclick="showtrimmedcontent(this);return false;">[Show trimmed content]</a><div id="trimmed" style="display:none;">'.substr($messages['message'],$parent).'</div>';
                   } ?>
                <?php echo $messages->message ?>
         </div>

</div>
    <br>
 </div>
