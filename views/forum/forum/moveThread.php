<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;

$this->title = AppUtility::t('Move Thread',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>

<form id="myForm" method="post" action="move-thread?forumid=<?php echo $forumId ?>&courseid=<?php echo $course->id ?>&threadid=<?php echo $threadId ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Thread'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/thread?forum='.$forumId.'&cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox ">
    <input type="hidden" id="thread-id" value="<?php echo $threadId ?>" >
    <div class="view-message-inner-contents">
        <div class="title-middle center"></div>
        <div class="title-option">
            <h4><?php AppUtility::t('What Do You Want To Do?');?>:</h4>
            <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td><input type='radio' checked name='movetype' value='0' onclick="select(0)">
            <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td >Move thread to different forum</td></div></tr>
            <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td><input type='radio' name='movetype' value='1' onclick="select(1)">
                            <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td >Move post to be a reply to a thread</td></div></tr>


        <div id="move-forum"><div class="title-middle-option center"><?php AppUtility::t('Move to forum');?></div>
            <div>
                <?php $currentTime = time();
                foreach ($forums as $forum) {
                    if($forum['forumid'] == $forumId)
                    {?>
                        <?php echo "<tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td><input type='radio' name='forum-name' checked id='".$forum['forumid']."' value='".$forum['forumid']."'><span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td>"." " ."<td>{$forum['forumName']}</td></div></tr>";?>

                    <?php }else{?>
                        <?php echo "<tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td><input type='radio' name='forum-name' id='".$forum['forumid']."' value='".$forum['forumid']."'><span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td>"." " ."<td>{$forum['forumName']}</td></div></tr>";?>
                    <?php           }?>
                <?php  } ?>
            </div>
        </div>

            <div id="move-thread"><div class="title-middle-option center"><?php AppUtility::t('Move to thread');?></div>
                <div>

                    <?php
                    $threadCount = 0;
                    foreach ($threads as $thread) {
                        ?>
                        <?php

                        if( $thread['forumIdData'] == $forumId && $thread['threadid'] != $threadId && $thread['parent'] == AppConstant::NUMERIC_ZERO ) { ?>
                            <?php echo "<tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'>
                            <td><input type='radio' name='thread-name' id='{$thread['threadid']}' value='{$thread['threadid']}'>
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span>
                                </label>
                                </td>"." " ."<td>{$thread['subject']}</td></div></tr>";?>
                        <?php }
                        if($thread['parent'] == 0){
                            $threadCount++;
                        }
                    } ?>
                </div>
            </div>
            <div class="buttons-div">
                <input type=submit class="btn btn-primary search-button align-btn" id="move-button" value="<?php echo AppUtility::t('Move')?>">
                <a class="btn btn-primary search-button align-btn margin-left-fifteen"
                   href="<?php echo AppUtility::getURLFromHome('forum/forum', 'thread?cid='.$course->id.'&forum='.$forumId)  ?>">
                    <?php echo AppUtility::t('Cancel')?></a>
            </div>

        </div>
    </div>
</div>
</form>