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
    <?php
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Thread'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/thread?forum='.$forumId.'&cid=' . $course->id]]);
      ?>
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
echo '<div class="tab-content shadowBox col-sm-12 col-md-12">';
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
    <input type="button" value="Expand All" onclick="toggleshowall()" id="toggleall"/>
    <button type="button" onclick="window.location.href='<?php echo AppUtility::getURLFromHome('forum','forum/list-post-by-name?cid='.$course->id.'&page='.$page.'&forumid='.$forumId.'&read=1');?>'">Mark All Read</button>
    <br><br>
</div>
<?php
$laststu = -1;
$cnt = 0;
if ($caneditscore && $haspoints) {
$urlmode = AppUtility::urlMode();?>
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
            echo "<div class='col-sm-12 col-md-12'><b>{$line['LastName']}, {$line['FirstName']}</b>";
            if ($line['hasuserimg']==1)
            {
                        $imageUrl = $line['userid'].".jpg";
                        ?><img class="circular-profile-image Align-link-post padding-five" id="img<?php echo $imgCount?>" src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl?>" onclick=changeProfileImage(this,<?php echo $line['userid']?>); /><?php
             }else {
                        ?><img class="circular-profile-image Align-link-post padding-five" id="img"src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/> <?php
                    }
            echo '</div>';
            echo '<div class="col-sm-12 col-md-12 padding-left-three-per">';
            $laststu = $line['userid'];
        }
        echo '<div class="col-sm-12 col-md-12 padding-left-zero block">';
        echo '<span class="col-sm-12 col-md-12" style="padding-right: 0">';
        if ($line['parent']!=0)
        {
            echo '<span class="col-sm-12 col-md-12 padding-left-zero" style="color:green; padding-right: 0">';
        }else{
            echo '<span class="col-sm-12 col-md-12 padding-left-zero" style="padding-right: 0">';
        }

        echo "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" />";
        echo '<span class="padding-left-five"><b class="word-break-break-all">'. $line['subject'].'</span></b>';

    if ($haspoints) {
        if ($caneditscore) {
            echo "<span class='padding-left-five'><input type=text size=6  maxlength='6' name=\"score[{$line['id']}]\" id=\"score{$line['id']}\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" value=\"";
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
    }
        $dt = AppUtility::tzdate("F j, Y, g:i a",$line['postdate']);
        echo '<span class=" " style="color:black;"><br>';
        echo 'Posted: '.$dt;
        echo '</span>';
        if ($line['lastview']==null || $line['postdate']>$line['lastview']) {
            echo " <span style=\"color:red;\">New</span>\n";
        }
            echo '</span>';

        echo '<span class="col-sm-5 col-md-5 padding-left-zero padding-top-six right" style="padding-right: 0; text-align: right">';?>

        <a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$course->id.'&forumid='.$forumId.'&threadid='.$line['threadid']);?> ">Thread</a>
        <?php if ($isteacher || ($line['ownerid']==$userId && $allowmod)) { ?>
        <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?courseId='.$course->id.'&forumId='.$forumId.'&threadId='.$line['id'] ) ?> ">Modify</a>
    <?php }
        if ($isteacher || ($allowdel && $line['ownerid']==$userId)) {
?>

            <input type="hidden" id="courseId" value="<?php echo $course->id;?>">
            <input type="hidden" id="forumId" value="<?php echo $forumId;?>">
            <a href="#" name="tabs" data-parent="<?php echo $line['parent'];?>" data-var="<?php echo $line['id'];?>" class="mark-remove" >Remove</a> <?php
        }
        if ($line['posttype']!=2 && $userRights > 5 && $allowreply) { ?>
            <a href="<?php echo AppUtility::getURLFromHome('forum','forum/reply-post?courseid='.$course->id.'&forumid='.$forumId.'&threadId='.$line['threadid'].'&listbypost=1&id='.$line['id'])?>">Reply</a>
        <?php }
        echo '</span>';

        echo '</span>';
        echo '</div>';
        echo '<div class="col-sm-12 col-md-12 padding-left-zero padding-right-zero">';
        echo "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']);

        if ($haspoints) {
            if ($caneditscore && $ownerid[$child] != $userId)
            {
                echo '<hr/>';
                echo '<div class="padding-bottom-one-em">';
                echo "<span class='padding-bottom-one-em padding-left-fifteen'>Private Feedback </span><textarea cols=\"50\" rows=\"2\" name=\"feedback[{$line['id']}]\" id=\"feedback{$line['id']}\">";
                if ($feedback[$line['id']]!==null) {
                    echo $feedback[$line['id']];
                }
                echo "</textarea>";
                echo "</div>";
            } else if (($ownerid[$child]==$userId || $canviewscore) && $feedback[$line['id']]!=null) {
                echo '<div class="padding-bottom-one-em signup padding-left-fifteen">Private Feedback ';
                echo $feedback[$line['id']];
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
        $cnt++;

    }?>
    <input type="hidden" id="cnt" value="<?php echo $cnt?>">
    <?php echo '</div>';
    if ($caneditscore && $haspoints) {
        echo "<div><input type=submit value=\"Save Grades\" /></div>";
        echo "</form>";
    }

    echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green; padding-right: 0\">Green: Reply</span></p>";
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
