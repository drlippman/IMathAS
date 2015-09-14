<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
$this->title = 'Add Rubric';
?>
<div class="item-detail-header"> <?php
    if ($from=='modq') {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Offline Grades'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $courseId, AppUtility::getHomeURL() .'gradebook/gradebook/gradebook?cid=' . $course->id]]);
//        $returnstr = 'modquestion.php?cid='.$cid.'&amp;aid='.$_GET['aid'].'&amp;id='.$_GET['qid'];
//        $curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Question Settings</a> ";
    } else if ($from=='addg') {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Offline Grades'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $courseId, AppUtility::getHomeURL() .'gradebook/gradebook/gradebook?cid=' . $course->id]]);
//    $returnstr = 'addgrades.php?cid='.$cid.'&amp;gbitem='.$_GET['gbitem'].'&amp;grades=all';
//    $curBreadcrumb .= "&gt; <a href=\"$returnstr\"></a> ";
    } else if ($from=='addf') {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Offline Grades'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $courseId, AppUtility::getHomeURL() .'gradebook/gradebook/gradebook?cid=' . $course->id]]);
//        $returnstr = 'addforum.php?cid='.$cid.'&amp;id='.$_GET['fid'];
//        $curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Forum</a> ";
    }
    echo '</div>';

?>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox margin-top-fourty">
    <div class="col-md-12 add-rubic-form">

<?php
if (isset($_GET['id'])) {
    $curBreadcrumb .= "&gt; <a href=\"addrubric.php?cid=$cid\">Manage Rubrics</a> ";
    if ($_GET['id']=='new') {
        $curBreadcrumb .= "&gt; Add Rubric\n";
        $pagetitle = "Add Rubric";
    } else {
        $curBreadcrumb .= "&gt; Edit Rubric\n";
        $pagetitle = "Edit Rubric";
    }
} else {
    $curBreadcrumb .= "&gt; Manage Rubrics\n";
    $pagetitle = "Manage Rubrics";
}
//BEGIN DISPLAY BLOCK

/******* begin html output ********/
if ($overwriteBody==1) {
    echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY
?>
    <?php
    if (!isset($params['id'])) {  //displaying "Manage Rubrics" page ?>
    <div class="col-md-12 edit-rubric-form padding-left-zero padding-top-twenty-five"><div class="col-md-12 margin-bottom-fifteen"><div class="col-md-3">Select a rubric to edit or</div> <div class="col-md-3"><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id=new')?>">Add a new rubric</a></div></div>
        <?php
        foreach($rubricsName as $rubricName){
        echo '<div class="col-md-12 padding-top-ten"><div class="col-md-3">';
            echo "{$rubricName['name']}" ?>
            </div>
            <div class="col-md-3">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id='.$rubricName['id'].$fromstr);?> ">Edit</a></div></div>
        <?php }
    echo '</div>';
        } else {
        /*  adding/editing a rubric
        *   Rubric Types
        *   1: score breakdown (record score and feedback)
        *   0: score breakdown (record score)
        *   3: score total (record score and feedback)
        *   4: score total (record score only)
        *   2: record feedback only (checkboxes)
        */
        $rubtypeval = array(1,0,3,4,2);
        $rubtypelabel = array('Score breakdown, record score and feedback','Score breakdown, record score only','Score total, record score and feedback','Score total, record score only','Feedback only'); ?>
    <form method="post" action="add-rubric?cid=<?php echo $course->id ?>&id=<?php echo $params['id']?><?php echo $fromstr?>">
            <?php echo '<input class="floatright margin-top-minus-nine-pt-five" type="submit" value="'.$savetitle.'"/><div class="col-md-12"><div class="col-md-2 select-text-margin">Name</div><div class="col-md-4">  <input class="form-control" type="text" size="70" name="rubname" value="'.str_replace('"','\\"',$rubname).'"/></div></div>';

        echo '<div class="col-md-12 margin-top-fifteen"><div class="col-md-2 select-text-margin">Rubric Type</div> <div class="col-md-4">';
        AssessmentUtility::writeHtmlSelect('rubtype',$rubtypeval,$rubtypelabel,$rubtype,null,null,'onchange="imasrubric_chgtype()"');
        echo '</div></div>';

        echo '<div class="col-md-12 margin-top-fifteen"><div class="col-md-offset-2 col-md-4">Share with Group<input class="margin-left-ten" type="checkbox" name="rubisgroup" '.AssessmentUtility::getHtmlChecked($rubgrp,-1,1).' /></div>';
        echo '
        <div class="col-md-12">
        <table class="width-hundread-per margin-top-fifteen">
        <thead><tr><th>Rubric Item<br/>Shows in feedback</th><th>Instructor Note<br/>Not in feedback</th><th><span id="pointsheader" ';
        if ($rubtype==2) {echo 'style="display:none;" ';}
        if ($rubtype==3 || $rubtype==4) {
            echo '>Percentage of score</span>';
        } else {
            echo '>Percentage of score<br/>Should add to 100</span>';
        }
        echo '</th></tr></thead><tbody>';
        for ($i=0;$i<15; $i++) {
            echo '<tr><td><input class="form-control" type="text" size="40" name="rubitem'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][0])) { echo str_replace('"','&quot;',$rubric[$i][0]);}
            echo '"/></td>';
            echo '<td><input class="form-control" type="text" size="40" name="rubnote'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][1])) { echo str_replace('"','&quot;',$rubric[$i][1]);}
            echo '"/></td>';
            echo '<td><input type="text" size="4" id="rubic-form-control" class="rubricpoints" ';
            if ($rubtype==2) {echo 'style="display:none;" ';}
            echo 'name="rubscore'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][2])) { echo str_replace('"','&quot;',$rubric[$i][2]);} else {echo 0;}
            echo '"/></td></tr>';
        }
        echo '</table></div>';
        echo '</form>
        </div>
        </div>';
        }
        }
        ?>
        <script>
            function imasrubric_chgtype() {
                var val = document.getElementById("rubtype").value;

                els = document.getElementsByTagName("input");
                console.log(els);
                for (i in els) {
                    if (els[i].className=='rubricpoints') {
                        if (val==2) {
                            els[i].style.display = 'none';
                            document.getElementById("pointsheader").style.display = 'none';
                        } else {
                            els[i].style.display = '';
                            document.getElementById("pointsheader").style.display = '';
                            if (val==0 || val==1) {
                                document.getElementById("pointsheader").innerHTML='Percentage of score<br/>Should add to 100';
                            } else if (val==3 || val==4) {
                                document.getElementById("pointsheader").innerHTML='Percentage of score';
                            }
                        }
                    }
                }
            }
        </script>