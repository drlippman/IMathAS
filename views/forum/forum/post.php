<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

//AppUtility::dump($postdata);
$this->title = 'Post';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['course/course/index?cid='.$_GET['cid']]];
//$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['forum/forum/search-forum?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
$currentLevel = 0;
?>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1">

<div id="postlabel">
    <div id="post">
        <h4><strong>Forum:</strong>&nbsp;&nbsp;<?php echo $postdata[0]['forumname'] ?></h4><br>
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

        <a href="#">Prev</a>&nbsp;
        <a href="#">Next</a> &nbsp;|&nbsp;
        <a href="#">Mark Unread</a>&nbsp;|
        <a href="#">Flag</a>&nbsp;
        <button onclick="expandall()" class="btn btn-primary">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary">Show All</button>
        <button onclick="hideall()" class="btn btn-primary">Hide All</button>
        <br><br>


        <?php $cnt = AppConstant::NUMERIC_ZERO;
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
    <?php }?>

    <?php } ?>

    <?php if ($data['level'] != 0 && $data['level'] > $currentLevel)
    {
    $cnt++;?>
    <div class="forumgrp" id="block<?php echo $index - 1 ?>">

        <?php }  ?>
        <div class=block><span class="leftbtns"><img class="pointer" id="butb<?php echo $index ?>"
                                                     src="<?php echo AppUtility::getHomeURL()?>img/collapse.gif"
                                                     onClick="toggleshow(<?php echo $index ?>)"/> </span>
                        <span class=right>
                      <?php if ($data['userRights'] >= AppConstant::STUDENT_RIGHT && $data['posttype'] != AppConstant::NUMERIC_TWO) {
                              if ($currentUser['rights'] > AppConstant::STUDENT_RIGHT) {
                                  ?>

                                  <a href="<?php echo AppUtility::getURLFromHome('forum','forum/move-thread?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Move</a>&nbsp;<a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;<a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a> <?php
                              } else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) { ?>
                              <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Modify</a><?php } ?>

                              <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumiddata']); ?>">
                                  Reply</a>
                          <?php } else if ($data['posttype'] == AppConstant::NUMERIC_TWO) {
                              if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] == AppConstant::STUDENT_RIGHT) { ?>
                              <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Modify</a><?php
                              } else if ($currentUser['id'] == $data['userId'] && $currentUser['rights'] > AppConstant::STUDENT_RIGHT) { ?>
                                  <a href="<?php echo AppUtility::getURLFromHome('forum','forum/move-thread?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Move</a>&nbsp;<a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumiddata'].'&courseId='.$courseid.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;<a>Remove</a><?php } ?>
                      <?php } else if ($data['posttype'] < strtotime(date('F d, o g:i a')) && $data['userRights'] == AppConstant::STUDENT_RIGHT) { ?>
                          <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?id=' . $data['id'] . '&threadId=' . $data['threadId'] . '&forumid=' . $data['forumiddata']); ?>">
                              Reply</a>
                      <?php } ?>
                            <input type=button class="btn btn-primary" id="buti<?php echo $index ?>" value="Hide"
                                   onClick="toggleitem(<?php echo $index ?>)">
                        </span>
            <b><?php echo $data['subject'] ?></b><br/>Posted by: <a
                href="mailto:<?php echo '#' ?>"><?php echo $data['name'] ?></a>, <?php echo $data['postdate'] ?>
            <?php
            if(strtotime($data['postdate'])>=$data['lastview']){?>
                   <span style="color:red;">New</span>
            <?php }?>
        </div>
        <div class="blockitems" id="item<?php echo $index ?>"><p><?php echo $data['message'] ?></p></div>
        <?php

        if ($index == (count($data) - AppConstant::NUMERIC_ONE))
        {

        for ($i = $cnt;
        $i > AppConstant::NUMERIC_ZERO;
        $i--)
        {
        ?>
    </div>
<?php }
}?>
    <?php
    $currentLevel = $data['level'];
    $postCount = (count($data) - 1);
    ?>
    <input type="hidden" id="postCount" value="<?php echo $postCount ?>">
    <?php } ?>
</div>
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js"></script>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/forum/post.js?ver=<?php echo time()?>"></script>
