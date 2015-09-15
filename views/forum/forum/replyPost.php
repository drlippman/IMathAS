<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t(' post Reply',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<form id="add-thread" enctype="multipart/form-data" action="<?php AppUtility::getURLFromHome('forum','forum/reply-post')?>" method="post">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forum:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn hide-hover">
            <a href="#" id="reply-btn" class="btn btn-primary1 pull-right  btn-color"><i class="fa fa-reply"></i>&nbsp;Post Reply</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
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
        <div class="col-sm-12 padding-top">
            <div class="col-sm-1"><?php echo AppUtility::t('Subject')?></div>
            <div class="col-sm-11"><input name="Subject" class="text-box subject" type="text" size="123" readonly value="Re: <?php echo $reply[0]['subject'] ?>">
            </div>
        </div>

        <div class="col-sm-12 padding-top" style="padding-bottom: 20px">
            <div class="col-sm-1"><?php echo AppUtility::t('Message')?></div>
            <?php echo "<div class='left col-sm-11'><div class= 'editor'>
        <textarea id='post-reply' name='post-reply' style='width: 70%;' rows='12' cols='20'></textarea></div></div><br>"; ?>
        </div>
    <div style="margin-left: 10.7%">
    <?php if($reply[0]['forumType'] == 1)
    { ?>
        <input name="file-0" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-0" placeholder="Description"><br>
        <br><button class="add-more">Add More Files</button><br>
    <?php }?>
    </div>
        <div  class="col-sm-12 replyTo padding-top">
            <div class=""><?php echo AppUtility::t('Replying To'); ?></div>
            <div class="block col-sm-12">
                <span class="leftbtns">
                </span>
                <span class="right">
                </span>
                <b  style="font-family: "Times New Roman", Times, serif"><?php echo $reply[0]['subject']?></b>
                <h5><b><?php echo AppUtility::t('Posted by'); ?></b>&nbsp;&nbsp;&nbsp;<?php echo $reply[0]['userName']?>,&nbsp;<?php echo $reply[0]['postDate']?></h5>
            </div>
            <div class="blockitems col-sm-12" id="item1">
                <h5><?php echo $reply[0]['message']?></h5></div>
        </div>


</div>
    </form>