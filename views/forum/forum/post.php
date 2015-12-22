<?php


use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Post';
$currentLevel = AppConstant::NUMERIC_ZERO;
?>
<div class="item-detail-header">
    <?php if($currentUser->rights > AppConstant::STUDENT_RIGHT) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]]);
    } else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]]);
    }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php
    if ($currentUser->rights > AppConstant::STUDENT_RIGHT) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } else {
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }
    ?>
</div>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" xmlns="http://www.w3.org/1999/html"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport">
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
<input type="hidden" id="tag-id" value="<?php echo $tagValue ?>">
<input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
<input type="hidden" id="user-id" value="<?php echo $currentUser['id'] ?>">
<?php $postBeforeView = (($forumData['settings'] & AppConstant::SIXTEEN) == AppConstant::SIXTEEN);
?>

<div class="tab-content shadowBox padding-top-one">
    <?php if(!$atLeastOneThread && $postBeforeView && !$canViewAll ){?>
     <p><?php AppUtility::t('This post is blocked. In this forum, you must post your own thread before you can read those posted by others.')?></p>
        <a class="pull-right" href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid=' . $course->id . '&forumid=' . $forumId);?>"><?php AppUtility::t('Back to Forum Topics')?></a>
        <?php }else {?>
        <div id="postlabel" class="padding-post-label">
<!--            <div id="post">-->
                <div class="post col-sm-12 col-md-12 midwrapper">
                <h4 class="word-break-break-all"><strong><?php AppUtility::t('Forum')?>:</strong>&nbsp;&nbsp;<?php echo $postdata[0]['forumName'] ?></h4><br>
                <h4 class="word-break-break-all"><strong><?php AppUtility::t('Post')?>:</strong>&nbsp;&nbsp;<?php echo $postdata[0]['subject'] ?></h4>
            </div>
        </div>

    <div class="mainbody padding-main-body">
        <div class="headerwrapper">
            <div id="navlistcont">
                <ul id="navlist"></ul>
                <div class="clear"></div>
            </div>
        </div>
        <div class="col-sm-12 col-md-12 midwrapper">
            <?php
            $a = AppConstant::NUMERIC_ZERO;
            foreach ($allThreadIds as $key => $threadFromAllThreadArray) {
                if ($threadFromAllThreadArray == $threadId) {
                    $a = $key;
                    break;
                }
            }
            if ($threadId > $prevNextValueArray['minThread']) { ?>
                <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?forumid=' . $forumId . '&courseid=' . $course->id . '&threadid=' . $allThreadIds[$a - 1]); ?>"><?php AppUtility::t('Prev')?></a>&nbsp;
            <?php } else { ?>
                <span><?php AppUtility::t('Prev')?></span>
            <?php
            }
            if ($threadId < $prevNextValueArray['maxThread']) { ?>
                <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?forumid=' . $forumId . '&courseid=' . $course->id . '&threadid=' . $allThreadIds[$a + 1]); ?>"><?php AppUtility::t('Next')?></a> &nbsp;
            <?php } else { ?>
                <span><?php AppUtility::t('Next')?></span>
            <?php } ?>
            <a href="#" onclick="markAsUnreadPost()"><?php AppUtility::t('Mark Unread')?></a>&nbsp;|
            <a href="#" id="flag-link" onclick="changeImage(false, <?php echo $threadId ?>)"><?php AppUtility::t('Flag')?></a>
            <a href="#" id="unflag-link" onclick="changeImage(true, <?php echo $threadId ?>)"><?php AppUtility::t('UnFlag')?></a>&nbsp;
            <button onclick="expandall()" class="btn post-btn-color expand"><?php AppUtility::t('Expand All')?></button>
            <button onclick="collapseall()" class="btn post-btn-color expand"><?php AppUtility::t('Collapse All')?></button>
            <button onclick="showall()" class="btn post-btn-color expand"><?php AppUtility::t('Show All')?></button>
            <button onclick="hideall()" class="btn post-btn-color expand"><?php AppUtility::t('Hide All')?></button>
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
        <div  class="padding-left-three-per col-sm-12 col-md-12 padding-left-zero padding-right-zero" id="block<?php echo $index - AppConstant::NUMERIC_ONE ?>">
        <?php } ?>
            <?php $imageUrl = $data['userId'] . '' . ".jpg"; ?>
            <div class="col-sm-12 col-md-12 padding-left-zero padding-right-zero block" id="<?php echo $data['id'] ?>">
            <span class="leftbtns col-sm-1 col-md-1">
                <?php if ($data['hasImg'] == AppConstant::NUMERIC_ONE){ ?>
                    <img class="circular-profile-image" id="img<?php echo $imgCount ?>"
                         src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>"
                         onclick=changeProfileImage(this,<?php echo $data['id'] ?>);>
                <?php
                } else {
                    ?>
                    <img class="circular-profile-image" id="img"
                         src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/>
                <?php } ?>
            </span>
                <span class="right col-sm-5 col-md-4 padding-left-zero">
                          <?php if ($data['userRights'] >= AppConstant::STUDENT_RIGHT && $data['postType'] != AppConstant::NUMERIC_TWO) {
                              if ($currentUser['rights'] > AppConstant::STUDENT_RIGHT) {
                                  ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/move-thread?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Move')?></a>&nbsp;
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Modify')?></a>&nbsp;
                                  <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove"><?php AppUtility::t('Remove')?></a> <?php
                              }
                              else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) { ?>
                               <?php if(($data['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO){   ?>
                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Modify')?></a>
                              <?php } ?>
                                  <?php if(($data['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR && $data['isReplies']== AppConstant::NUMERIC_ZERO){  ?>
                                      <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove"><?php AppUtility::t('Remove')?></a>
                                  <?php  } ?>
                              <?php }  ?>
                               <?php  if($currentUser['rights'] == AppConstant::STUDENT_RIGHT ){ ?>
                                  <?php if(($replyBy == AppConstant::ALWAYS_TIME || $replyBy === null)  && $allowReply){?>
                                 <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>"> <?php AppUtility::t('Reply')?> </a>
                                       <?php }
                                  elseif($replyBy > $now && $allowReply){ ?>
                                      <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>">  <?php AppUtility::t('Reply')?></a>
                                  <?php } ?>
                             <?php }
                               else if($currentUser['rights'] > AppConstant::STUDENT_RIGHT ){ ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id . '&id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumIdData']); ?>"> <?php AppUtility::t('Reply')?></a>
                              <?php } ?>
                    <?php
                          }
                          else if ($data['postType'] == AppConstant::NUMERIC_TWO)
                          {
                              if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) {
                                  ?>
                                  <?php if(($data['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO){   ?>
                                      <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Modify')?></a>
                                  <?php } ?>
                                  <?php if(($data['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR && $data['isReplies']== AppConstant::NUMERIC_ZERO){  ?>
                                      <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove"><?php AppUtility::t('Remove')?></a>
                                  <?php  } ?>
                             <?php } else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] > AppConstant::STUDENT_RIGHT) {
                                  ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/move-thread?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Move')?></a>&nbsp;
                                  <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/modify-post?forumId=' . $data['forumIdData'] . '&courseId=' . $course->id . '&threadId=' . $data['id']); ?>"><?php AppUtility::t('Modify')?></a>&nbsp;
                              <a href="#" name="remove" data-parent="<?php echo $data['parent'] ?>" data-var="<?php echo $data['id'] ?>" class="mark-remove"> <?php AppUtility::t('Remove')?></a><?php } ?>
                          <?php }  ?>
                    <input type=button class="hide-button" id="buti<?php echo $index ?>" value="Hide"
                           onClick="toggleitem(<?php echo $index ?>)">
                            </span>
                <span class="col-sm-6 col-md-7 word-break-break-all">
                <b><?php echo $data['subject'] ?></b><br/><?php AppUtility::t('Posted by')?>: <a
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $data['userId'] . '&new=1') ?>"><?php echo $data['name'] ?></a>

                    <?php if($currentUser['rights'] > AppConstant::STUDENT_RIGHT && $data['userRights'] == AppConstant::NUMERIC_TEN){ ?>
                            <sub><a href="#">[<?php AppUtility::t('GB')?>]</a></sub>
                    <?php } ?>
                <?php echo $data['postdate'] ?>
                <?php

                if ((strtotime($data['postdate']) > $data['lastView']) && ($data['id'] != $data['threadId'])) {

                    ?>
                    <span style="color:red;"><?php AppUtility::t('New')?></span>
                <?php } ?>
                <?php $allowLikes = (($data['settings'] & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT);
                if ($allowLikes) { ?>
                    <?php if ($data['likeImage'] == AppConstant::NUMERIC_ONE) { ?>
                        &nbsp;&nbsp;
                        <img id="like-icon" class="" src="<?php echo AppUtility::getAssetURL() ?>img/liked.png" title="" onclick="saveLikes(this,false,<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)">
                    <?php } else { ?>
                        &nbsp;&nbsp;
                        <img id="like-icon" class="" src="<?php echo AppUtility::getAssetURL() ?>img/likedgray.png" title="" onclick="saveLikes(this,true,<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)">
                    <?php } ?>
                    <?php if ($data['likeCnt'] == AppConstant::NUMERIC_ZERO) {
                        $data['likeCnt'] = '';
                    } else {
                        $data['likeCnt'] = $data['likeCnt'];
                    } ?>
                    <span class="pointer" id="likeCnt<?php echo $data['id'] ?>"
                          onclick=countPopup(<?php echo $data['id'] ?>,<?php echo $data['threadId'] ?>,<?php echo $data['postType'] ?>)><label><?php echo $data['likeCnt'] ?></label></span>
                <?php } ?>
        </span>
        </div>
            <div class="col-sm-12 col-md-12 padding-left-zero padding-right-zero blockitems" id="item<?php echo $index ?>">
                <?php if($data['fileType'])
                {
                    if($data['files'])
                    {
                        $fl = explode('@@',$data['files']);
                        if (count($fl) > AppConstant::NUMERIC_TWO)
                        { ?>
                             <p><b><?php AppUtility::t('Files')?>:</b>
                        <?php }else
                        { ?>
                             <p><b><?php AppUtility::t('File')?>:</b>
                        <?php }
                        for ($i = AppConstant::NUMERIC_ZERO;$i < count($fl)/AppConstant::NUMERIC_TWO; $i++){?>
                            <a href="<?php echo AppUtility::getAssetURL()?>Uploads/forumFiles/<?php echo $fl[2*$i+1]?>" target="_blank"><?php echo $fl[2*$i];?></a>
                      <?php }
                    }
                }?>
                <p><?php echo $data['message'] ?></p>
            </div>
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
        <div class="pull-right margin-bottom-twentyfive">
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $course->id . '&forumid=' . $forumId) ?>"><?php AppUtility::t('Back to Forum Topics')?></a>
        </div>
    </div>
    <?php } ?>
</div>
