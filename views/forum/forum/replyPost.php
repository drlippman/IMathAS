<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t(' post Reply',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<form id="add-thread" enctype="multipart/form-data" action="<?php AppUtility::getURLFromHome('forum','forum/reply-post')?>" method="post">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Thread'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/thread?forum='.$forumId.'&cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forum:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
    <div class="item-detail-content">
        <?php if($currentUser['rights'] > 10) {
            echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
        } elseif($currentUser['rights'] == 10){
            echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
        }?>
    </div>
<input type="hidden" class="forum-id" value="<?php echo $forumId ?>">
<input type="hidden" class="course-id" value="<?php echo $course->id ?>">
<input type="hidden" class="thread-id" value="<?php echo $threadId ?>">
<input type="hidden" name="parentId" class="parent-id" value="<?php echo $parentId ?>">
<?php if($isPost){?>
<input type="hidden" name="isPost" class="isPost" value="<?php echo $isPost ?>">
<?php }?>

<div class="tab-content shadowBox">
        <p></p>
        <br>
        <div class="col-sm-12 col-md-12 padding-top">
            <div class="col-sm-1 col-md-1"><?php echo AppUtility::t('Subject')?></div>
            <div class="col-sm-11 col-md-11">
                <input name="Subject" class="form-control min-width-hundred-per text-box subject" type="text"  readonly value="Re: <?php echo $reply[0]['subject'] ?>">
            </div>
        </div>

        <div class="col-sm-12 col-md-12 padding-top" style="padding-bottom: 20px">
            <div class="col-sm-1 col-md-1 "><?php echo AppUtility::t('Message')?></div>
            <?php echo "<div class='max-width-hundred-per left col-md-11 col-sm-11'>
        <div class= 'editor post-reply-message-textarea'>
        <textarea id='post-reply' name='post-reply' style='width: 70%;' rows='12' cols='20'></textarea>
        </div></div><br>"; ?>
        </div>
    <?php
    if (!$isTeacher && $allowanon==1) {
    foreach($threadData as $data) {
            echo "<span class=form>Post Anonymously:</span><span class=formright>";
            echo "<input type=checkbox name=\"postanon\" value=1 ";
            if ($data['isanon']==1) {echo "checked=1";}
            echo "></span><br class=form/>";
        }
    }
    ?>
    <div style="margin-left: 10.7%">
    <?php if($reply[0]['forumType'] == 1)
    {
        ?>
        <input name="file-0" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-0" placeholder="Description"><br>
        <br><button class="add-more">Add More Files</button><br>
    <?php }?>
    </div>
    <div class="header-btn hide-hover col-sm-6 col-sm-offset-1 col-md-6 col-md-offset-1 padding-left-twenty-eight">
        <a href="#" id="reply-btn" class="btn btn-primary1 btn-color"><i class="fa fa-reply"></i>&nbsp;Post Reply</a>
    </div>
        <div  class="col-sm-12 col-md-12 replyTo padding-top">
            <div class=""><?php echo AppUtility::t('Replying To'); ?></div>
            <div class="block col-sm-12 col-md-12">
                <span class="leftbtns">
                </span>
                <span class="right">
                </span>
                <b  style="font-family: Times New Roman, Times, serif"><?php echo $reply[0]['subject']?></b>
                <h5><b><?php echo AppUtility::t('Posted by'); ?></b>&nbsp;&nbsp;&nbsp;

                   <?php
                  if ($reply[0]['postanon']==1) {
                      echo 'Anonymous';
                    } else {
                       echo $reply[0]['userName'];
                    }
                   ?>,&nbsp;<?php echo $reply[0]['postDate']?></h5>
            </div>
            <div class="blockitems col-sm-12 col-md-12" id="item1">
                <h5><?php echo $reply[0]['message']?></h5></div>
        </div>
</div>
    </form>