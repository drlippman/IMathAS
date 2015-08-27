<?php


use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Post';
if ($currentUser->rights > AppConstant::STUDENT_RIGHT) {
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
} else {
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid=' . $course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]];
$this->params['breadcrumbs'][] = $this->title;
$currentLevel = AppConstant::NUMERIC_ZERO;
?>

<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" xmlns="http://www.w3.org/1999/html"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1">

<input type="hidden" id="course-id" value="<?php echo $course->id ?>">

<input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
<input type="hidden" id="tag-id" value="<?php echo $tagValue ?>">
<input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
<input type="hidden" id="user-id" value="<?php echo $currentUser['id'] ?>">
<?php $postBeforeView = (($forumData['settings']&16)==16);
?>
<?php if($postBeforeView && !$canViewAll){?>
 <p>This post is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>
    <a class="pull-right" href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid=' . $course->id . '&forumid=' . $forumId);?>">Back to Forum Topics</a>
    <?php }else {?>
    <div id="postlabel">
    <div id="post">
        <h4><strong>Forum:</strong>&nbsp;&nbsp;<?php echo $postdata[0]['forumName'] ?></h4><br>
        <h4><strong>Post:</strong>&nbsp;&nbsp;<?php echo $postdata[0]['subject'] ?></h4>

    </div>
</div>

<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist"></ul>
            <div class="clear"></div>
        </div>
    </div>
    <div class="midwrapper">
        <?php
        $a = 0;
        foreach ($allThreadIds as $key => $threadFromAllThreadArray) {
            if ($threadFromAllThreadArray == $threadId) {
                $a = $key;
                break;
            }
        }
        if ($threadId > $prevNextValueArray['minThread']) { ?>
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?forumid=' . $forumId . '&courseid=' . $course->id . '&threadid=' . $allThreadIds[$a - 1]); ?>">Prev</a>&nbsp;
        <?php } else { ?>
            <span>Prev</span>
        <?php
        }
        if ($threadId < $prevNextValueArray['maxThread']) { ?>
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?forumid=' . $forumId . '&courseid=' . $course->id . '&threadid=' . $allThreadIds[$a + 1]); ?>"">Next</a> &nbsp;
        <?php } else { ?>
            <span>Next</span>
        <?php } ?>
        <a href="#" onclick="markAsUnreadPost()">Mark Unread</a>&nbsp;|
        <a href="#" id="flag-link" onclick="changeImage(false, <?php echo $threadId ?>)">Flag</a><a href="#"
                                                                                                    id="unflag-link"
                                                                                                    onclick="changeImage(true, <?php echo $threadId ?>)">UnFlag</a>&nbsp;
        <button onclick="expandall()" class="btn btn-primary expand">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary expand">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary expand">Show All</button>
        <button onclick="hideall()" class="btn btn-primary expand">Hide All</button>

        <br><br>
    <?php $cnt = AppConstant::NUMERIC_ZERO;
        $now = time();
        foreach ($postdata as $index => $data){
        ?>
        <?php if ($data['level'] != AppConstant::NUMERIC_ZERO && $data['level'] < $currentLevel)
        {
        $cnt--;

        for ($i = $currentLevel;
        $data['level'] < $i;
        $i--){
        ?>
    </div>
    <?php }
    } ?>
    <?php if ($data['level'] != AppConstant::NUMERIC_ZERO && $data['level'] > $currentLevel)
    {
    $cnt++;?>
    <div class="forumgrp" id="block<?php echo $index - AppConstant::NUMERIC_ONE ?>">

        <?php } ?>
        <?php $imageUrl = $data['userId'] . '' . ".jpg"; ?>
        <div class=block id="<?php echo $data['id'] ?>"><span class="leftbtns">
                <?php if ($data['hasImg'] == AppConstant::NUMERIC_ONE){ ?>
                <img class="circular-profile-image" id="img<?php echo $imgCount ?>"
                     src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>"
                     onclick=changeProfileImage(this,<?php echo $data['id'] ?>);> </span>
            <?php
            } else {
                ?>
                <img class="circular-profile-image" id="img"
                     src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/></span>
            <?php } ?>
            <span class=right>
                      <?php if ($data['userRights'] >= AppConstant::STUDENT_RIGHT && $data['postType'] != AppConstant::NUMERIC_TWO) {
                          if ($currentUser['rights'] > AppConstant::STUDENT_RIGHT) {
                              ?>

                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/move-thread?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Move</a>&nbsp;

                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Modify</a>&nbsp;
                              <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove">Remove</a> <?php
                          }
                          else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) { ?>
                           <?php if(($data['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO){   ?>
                          <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Modify</a>
                          <?php } ?>
                              <?php if(($data['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR && $data['isReplies']== AppConstant::NUMERIC_ZERO){  ?>
                                  <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove">Remove</a>
                              <?php  } ?>
                          <?php }  ?>

                           <?php  if($currentUser['rights'] == AppConstant::STUDENT_RIGHT ){ ?>
                              <?php if(($replyBy == AppConstant::ALWAYS_TIME || $replyBy === null)  && $allowReply){?>
                             <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>">  Reply</a>
                                   <?php }
                              elseif($replyBy > $now && $allowReply){ ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>">  Reply</a>
                              <?php } ?>
                         <?php }
                           else if($currentUser['rights'] > AppConstant::STUDENT_RIGHT ){ ?>
                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>"> Reply</a>
                          <?php } ?>
                <?php
                      }
                      else if ($data['postType'] == AppConstant::NUMERIC_TWO) {
                          if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) {
                              ?>
                              <?php if(($data['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO){   ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Modify</a>
                              <?php } ?>
                              <?php if(($data['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR && $data['isReplies']== AppConstant::NUMERIC_ZERO){  ?>
                                  <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove">Remove</a>
                              <?php  } ?>
                         <?php } else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] > AppConstant::STUDENT_RIGHT) {
                              ?>
                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/move-thread?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Move</a>&nbsp;

                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>">Modify</a>&nbsp;
                          <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove"> Remove</a><?php } ?>
                      <?php }  ?>
                <input type=button class="btn btn-primary" id="buti<?php echo $index ?>" value="Hide"
                       onClick="toggleitem(<?php echo $index ?>)">
                        </span>
            <b><?php echo $data['subject'] ?></b><br/>Posted by: <a
            <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $data['userId'] . '&new=1') ?>"><?php echo $data['name'] ?></a>
                <?php if($currentUser['rights'] > AppConstant::STUDENT_RIGHT && $data['userRights'] == AppConstant::NUMERIC_TEN){ ?>
                        <sub><a href="#">[GB]</a></sub>
                <?php } ?>
            <?php echo $data['postdate'] ?>

            <?php
            if (strtotime($data['postdate']) >= $data['lastView'] && $data['id'] != $data['threadId']) {
                ?>
                <span style="color:red;">New</span>
            <?php } ?>
            <?php $allowLikes = (($data['settings'] & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT);
            if ($allowLikes) { ?>
                <?php if ($data['likeImage'] == 1) { ?>
                    &nbsp;&nbsp;<img id="like-icon" class="" src="<?php echo AppUtility::getAssetURL() ?>img/liked.png"
                                     title=""
                                     onclick="saveLikes(this,false,<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)">
                <?php } else { ?>
                    &nbsp;&nbsp;<img id="like-icon" class=""
                                     src="<?php echo AppUtility::getAssetURL() ?>img/likedgray.png" title=""
                                     onclick="saveLikes(this,true,<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)">
                <?php } ?>
                <?php if ($data['likeCnt'] == AppConstant::NUMERIC_ZERO) {
                    $data['likeCnt'] = '';
                } else {
                    $data['likeCnt'] = $data['likeCnt'];
                } ?>
                <span class="pointer" id="likeCnt<?php echo $data['id'] ?>"
                      onclick=countPopup(<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)><label><?php echo $data['likeCnt'] ?></label></span>
            <?php } ?>
        </div>
        <div class="blockitems" id="item<?php echo $index ?>"><p><?php echo $data['message'] ?></p></div>

        <?php
        if ($index == count($postdata) - AppConstant::NUMERIC_ONE)
        {

        for ($i = $cnt;
        $i >= AppConstant::NUMERIC_ZERO;
        $i--)
        {
        ?>
    </div>
<?php
}
}?>

    <?php
    $currentLevel = $data['level'];
    $postCount = (count($postdata) - AppConstant::NUMERIC_ONE);
    ?>
    <input type="hidden" id="postCount" value="<?php echo $postCount ?>">
    <?php } ?>
    <div class=" pull-right">
        <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $course->id . '&forumid=' . $forumId) ?>">Back
            to Forum Topics</a>
    </div>
</div>
<?php } ?>
