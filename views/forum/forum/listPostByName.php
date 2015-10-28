<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
$this->title = AppUtility::t('List Post By Name',false );
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if($userRights == 100 || $userRights == 20) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id]]);
    } elseif($userRights == 10){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/index?cid=' . $course->id]]);
    }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($userRights == 100 || $userRights == 20) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($userRights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>

<?php
echo '<div class="tab-content shadowBox ">';
echo '<div id="headerpostsbyname" class="margin-left-twenty pagetitle">';
echo "<h2>Posts by Name - $forumname</h2>\n";
echo '</div>';
?>
<?php

if ($haspoints && $caneditscore && $rubric != 0) {
    if (count($rubricData) > 0)
    {

        echo printrubrics(array($rubricDataRow));
    }
}

?>
<div class="midwrapper margin-left-twenty">
    <!--    <input type="button" id="expand" onclick="collapseall()" class="btn btn-primary add-new-thread" value="Expand All">-->
    <input type="button" value="Expand All" onclick="toggleshowall()" id="toggleall"/>
    <button type="button" onclick="window.location.href='<?php echo AppUtility::getURLFromHome('forum','forum/list-post-by-name?cid='.$course->id.'&page='.$page.'&forumid='.$forumId.'&read=1');?>'">Mark All Read</button>
    <br><br>
</div>
<?php
$laststu = -1;
$cnt = 0;
if ($caneditscore && $haspoints) { ?>
<form method=post action="thread?cid=<?php echo $course->id ?>&forum=<?php echo $forumId?>&page=<?php echo $page?>&score=true" onsubmit="onsubmittoggle()">
    <?php }
    $curdir = rtrim(dirname(__FILE__), '/\\');
    echo '<div class="margin-left-twenty padding-right-twenty">';
    foreach ($posts as $line)
    {
        if ($line['userid']!=$laststu) {
            if ($laststu!=-1) {
                echo '</div>';
            }
            echo "<b>{$line['LastName']}, {$line['FirstName']}</b>";
            if ($line['hasuserimg']==1)
            {
                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$line['userid']}.jpg\"  onclick=\"changeProfileImage(this,{$line['userid']})\"  />";
                } else {
                    $imageUrl = $line['userid'].".jpg";
                    ?><img class="circular-profile-image Align-link-post padding-five" id="img<?php echo $imgCount?>"src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl?>" onclick=changeProfileImage(this,<?php echo $line['userid']?>); /><?php
                }
            }else {
                ?><img class="circular-profile-image Align-link-post padding-five" id="img"src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/> <?php
            }
            echo '<div class="forumgrp">';
            $laststu = $line['userid'];
        }
        echo '<div class="block">';
        if ($line['parent']!=0) {
            echo '<span style="color:green;">';
        }

        echo '<span class="right">';
        if ($haspoints) {
            if ($caneditscore) {
                echo "<input type=text size=2 name=\"score[{$line['id']}]\" id=\"score{$line['id']}\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" value=\"";
                if (isset($scores[$line['id']])) {
                    echo $scores[$line['id']];
                }
                echo "\"/> Pts ";
                if ($rubric != 0) {
                    echo printrubriclink($rubric,$pointspos,"score{$line['id']}", "feedback{$line['id']}").' ';
                }
            } else if (($line['ownerid']==$userId || $canviewscore) && isset($scores[$line['id']])) {
                echo "<span class=red>{$scores[$line['id']]} pts</span> ";
            }
        } ?>
        <a href="<?php echo AppUtility::getURLFromHome('forum','forum/posts?courseid='.$course->id.'&forumid='.$forumId.'&threadid='.$line['threadid']);?> ">Thread</a>
        <?php if ($isteacher || ($line['ownerid']==$userId && $allowmod)) { ?>
        <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?courseId='.$course->id.'&forumId = '.$forumId.'&threadId='.$line['id'] ) ?> ">Modify</a>
    <?php }
        if ($isteacher || ($allowdel && $line['ownerid']==$userId)) {
//        echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&remove={}\">Remove</a> \n";
            ?><a href="#" name="tabs" data-var="<?php echo $line['id']?>" class="mark-remove" >Remove</a> <?php
        }
        if ($line['posttype']!=2 && $userRights > 5 && $allowreply) { ?>
            <!--        /forum/forum/?courseid=477&threadId=80923&forumid=19893&id=80929&listbypost=1-->
            <a href="<?php echo AppUtility::getURLFromHome('forum','forum/reply-post?courseid='.$course->id.'&forumid='.$forumId.'&threadId='.$line['threadid'].'&listbypost=1&id='.$line['id'])?>">Reply</a>
        <?php }
        echo '</span>';
        echo "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" />";
        echo '<b>'.$line['subject'].'</b>';
        if ($line['parent']!=0) {
            echo '</span>';
        }
        $dt = AppUtility::tzdate("F j, Y, g:i a",$line['postdate']);
        echo ', Posted: '.$dt;
        if ($line['lastview']==null || $line['postdate']>$line['lastview']) {
            echo " <span style=\"color:red;\">New</span>\n";
        }
        echo '</div>';
        echo "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']);

        if ($haspoints) {
            if ($caneditscore && $ownerid[$child] != $userId)
            {
                echo '<hr/>';
                echo "Private Feedback: <textarea cols=\"50\" rows=\"2\" name=\"feedback[{$line['id']}]\" id=\"feedback{$line['id']}\">";
                if ($feedback[$line['id']]!==null) {
                    echo $feedback[$line['id']];
                }
                echo "</textarea>";
            } else if (($ownerid[$child]==$userId || $canviewscore) && $feedback[$line['id']]!=null) {
                echo '<div class="signup">Private Feedback: ';
                echo $feedback[$line['id']];
                echo '</div>';
            }
        }
        echo '</div>';
        $cnt++;
    }
    echo '</div>';
    echo "<script>var bcnt = $cnt;</script>";
    if ($caneditscore && $haspoints) {
        echo "<div><input type=submit value=\"Save Grades\" /></div>";
        echo "</form>";
    }

    echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
    ?>
    <p><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$course->id.'&forum='.$forumId.'&page='.$page); ?> ">Back to Thread List</a></p>
    <?php echo '</div>';
    echo '</div>';

    function printrubriclink($rubricid, $points, $scorebox, $feedbackbox, $qn = 'null', $width = 600)
    {
        $out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
        $out .= "<img border=0 src='../../img/assess.png' alt=\"rubric\"></a>";
        return $out;
    }
    function printrubrics($rubricarray) {

        $out = '<script type="text/javascript">';
        $out .= 'var imasrubrics = new Array();';
        foreach ($rubricarray as $info) {
            $out .= "imasrubrics[{$info[0]}] = {'type':{$info[1]},'data':[";
            $data = unserialize($info[2]);

            foreach ($data as $i=>$rubline) {
                if ($i!=0) {
                    $out .= ',';
                }
                $out .= '["'.str_replace('"','\\"',$rubline[0]).'",';
                $out .= '"'.str_replace('"','\\"',$rubline[1]).'"';
                $out .= ','.$rubline[2];
                $out .= ']';
            }
            $out .= ']};';
        }
        $out .= '</script>';

        return $out;
    }

    ?>
