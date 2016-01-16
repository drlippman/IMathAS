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
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $courseId . '&forum=' . $forumid]]);
    } else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $courseId . '&forum=' . $forumid]]);
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
<input type="hidden" id="course-id" value="<?php echo $courseId ?>">
<input type="hidden" id="forum-id" value="<?php echo $forumid ?>">
<input type="hidden" id="tag-id" value="<?php echo $tagValue ?>">
<input type="hidden" id="thread-id" value="<?php echo $threadid ?>">
<input type="hidden" id="user-id" value="<?php echo $currentUser['id'] ?>">

<div class="tab-content shadowBox padding-top-one">

    <?php
    if (!$oktoshow) {

    } else{
        echo "<br/><b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
        echo "<b style=\"font-size: 100%\">Forum: $forumname</b></p>";

        $nextth = '';
        $prevth = '';

        if (($resultPrev) > 0) {
            $prevth = $resultPrev['id'];
            echo "<a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$prevth&grp=$groupid\">Prev</a> ";
        } else {
            echo "Prev ";
        }

        if (($resultNext) > 0) {
            $nextth = $resultNext['id'];
            echo "<a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$nextth&grp=$groupid\">Next</a> ";
        } else {
            echo "Next";
        }
        echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&markunread=true\">Mark Unread</a>";
        if ($tagged) {
            echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&markuntagged=true\">Unflag</a>";
        } else {
            echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&marktagged=true\">Flag</a>";
        }



        echo '| <button onclick="expandall()">'._('Expand All').'</button>';
        echo '<button onclick="collapseall()">'._('Collapse All').'</button> | ';
        echo '<button onclick="showall()">'._('Show All').'</button>';
        echo '<button onclick="hideall()">'._('Hide All').'</button>';


        if ($view==2) {
            echo "<a href=\"posts?view=$view&courseid=$courseId&forumid=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
        } else {
            echo "<a href=\"post?view=$view&courseid=$courseId&forumid=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
        }
        echo "<br/>";echo "<br/>";
        $printChildren = new AppUtility();
        $printChildren->printchildren(0);
    }
    ?>
</div>

<script type="text/javascript">
    function toggleshow(bnum) {
        var node = document.getElementById('block'+bnum);
        var butn = document.getElementById('butb'+bnum);
        if (node.className == 'forumgrp') {
            node.className = 'hidden';
            //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
            //       butn.value = 'Expand';
            butn.src = imasroot+'/img/expand.gif';
        } else {
            node.className = 'forumgrp';
            //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
            //       butn.value = 'Collapse';
            butn.src = imasroot+'/img/collapse.gif';
        }
    }
    function toggleitem(inum) {
        var node = document.getElementById('item'+inum);
        var butn = document.getElementById('buti'+inum);
        if (node.className == 'blockitems') {
            node.className = 'hidden';
            butn.value = 'Show';
        } else {
            node.className = 'blockitems';
            butn.value = 'Hide';
        }
    }
    function expandall() {
        for (var i=0;i<bcnt;i++) {
            var node = document.getElementById('block'+i);
            var butn = document.getElementById('butb'+i);
            node.className = 'forumgrp';
            //     butn.value = 'Collapse';
            //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
            butn.src = imasroot+'/img/collapse.gif';
        }
    }
    function collapseall() {
        for (var i=0;i<bcnt;i++) {
            var node = document.getElementById('block'+i);
            var butn = document.getElementById('butb'+i);
            node.className = 'hidden';
            //     butn.value = 'Expand';
            //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
            butn.src = imasroot+'/img/expand.gif';
        }
    }

    function showall() {
        for (var i=0;i<icnt;i++) {
            var node = document.getElementById('item'+i);
            var buti = document.getElementById('buti'+i);
            node.className = "blockitems";
            buti.value = "Hide";
        }
    }
    function hideall() {
        for (var i=0;i<icnt;i++) {
            var node = document.getElementById('item'+i);
            var buti = document.getElementById('buti'+i);
            node.className = "hidden";
            buti.value = "Show";
        }
    }
    function savelike(el) {
        var like = (el.src.match(/gray/))?1:0;
        var postid = el.id.substring(8);
        $(el).parent().append('<img style="vertical-align: middle" src="../img/updating.gif" id="updating"/>');
        $.ajax({
            url: "recordlikes.php",
            data: {cid:<?php echo $courseId;?>, postid: postid, like: like},
            dataType: "json"
        }).done(function(msg) {
            if (msg.aff==1) {
                el.title = msg.msg;
                $('#likecnt'+postid).text(msg.cnt>0?msg.cnt:'');
                el.className = "likeicon"+msg.classn;
                if (like==0) {
                    el.src = el.src.replace("liked","likedgray");
                } else {
                    el.src = el.src.replace("likedgray","liked");
                }
            }
            $('#updating').remove();
        });
    }
</script>
