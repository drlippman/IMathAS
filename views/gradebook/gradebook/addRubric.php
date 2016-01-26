<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
?>
<div class="item-detail-header"> <?php
    if (isset($params['id']) || isset($params['nomanage'])) {
        if ($from=='modq') {
            $breadcrumb = $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Modify Question Settings','Manage Rubrics'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' .$course->id , AppUtility::getHomeURL() .'gradebook/gradebook/modify-question?cid=' . $course->id.'aid='.$params['aid'].'id='.$params['qid'],AppUtility::getHomeURL() .'gradebook/gradebook/add-rubric?cid='.$course->id]]);
        } else if ($from=='addg') {
            $breadcrumb =  $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Offline Grades','Manage Rubrics'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() .'gradebook/gradebook/add-grades?cid=' . $course->id.'&gbitem='.$params['gbitem'].'&grades=all',AppUtility::getHomeURL() .'gradebook/gradebook/add-rubric?cid='.$course->id]]);
        } else if ($from=='addf') {
            $breadcrumb =  $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Modify Forum','Manage Rubrics'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() .'gradebook/gradebook/add-forum?cid=' . $course->id.'id='.$params['fid'],AppUtility::getHomeURL() .'gradebook/gradebook/add-rubric?cid='.$course->id]]);
        }else{
            $breadcrumb =  $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Manage Rubrics'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id ,AppUtility::getHomeURL() .'gradebook/gradebook/add-rubric?cid='.$course->id]]);
        }

        if ($params['id']=='new') {
            $this->title = "Add Rubric";
        } else {
            $this->title = "Edit Rubric";
        }
    } else {
        $breadcrumb =  $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]);
        $this->title = "Manage Rubrics";
    }
    echo $breadcrumb;
    echo '</div>';
?>
    <form method="post" action="add-rubric?cid=<?php echo $course->id ?>&id=<?php echo $params['id']?><?php echo $fromstr?>">
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content">
        <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']); ?>
    </div>
<div class="tab-content shadowBox">


    <div class="col-md-12 col-sm-12 add-rubic-form">

<?php

//BEGIN DISPLAY BLOCK

/******* begin html output ********/
if ($overwriteBody==1) {
    echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY
?>
    <?php
    if (!isset($params['id'])) {  //displaying "Manage Rubrics" page ?>
    <div class="col-md-12 col-sm-12 edit-rubric-form padding-left-zero padding-top-twenty-five">
        <div class="col-md-12 col-sm-12 margin-bottom-fifteen">
            <div class="col-md-3 col-sm-4">Select a rubric to edit or</div>
            <div class="col-md-3 col-sm-4">
                <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id=new')?>">Add a new rubric</a>
            </div>
        </div>
        <?php
        foreach($rubricsName as $rubricName){
        echo '<div class="col-md-12 col-sm-12 padding-top-ten">
        <div class="col-sm-4 col-md-3 word-break-break-all">';
            echo "{$rubricName['name']}" ?>
            </div>
            <div class="col-md-3 col-sm-3">
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

            <?php echo '<div class="col-sm-12 col-md-12">
            <div class="col-sm-2 col-md-2 select-text-margin">Name</div>
            <div class="col-sm-6 col-md-4"> <input class="form-control" type="text" size="70" name="rubname" value="'.str_replace('"','\\"',$rubname).'"/></div></div>';

        echo '<div class="col-sm-12 col-md-12 padding-top-fifteen">
        <div class="col-sm-2 col-md-2 select-text-margin">Rubric Type</div>
        <div class="col-sm-6 col-md-4">';
        AssessmentUtility::writeHtmlSelectRubric('rubtype',$rubtypeval,$rubtypelabel,$rubtype,null,null,'onchange="imasrubric_chgtype()"');
        echo '</div></div>';

        echo '<div class="col-sm-12 col-md-12 padding-top-fifteen padding-bottom-five">
        <div class="col-sm-offset-2 col-sm-4 col-md-offset-2 col-md-4">Share with Group<input class="margin-left-ten" type="checkbox" name="rubisgroup" '.AssessmentUtility::getHtmlChecked($rubgrp,-1,1).' /></div>';
        echo '
        <div class="col-md-12 col-sm-12">
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
        echo '</table></div>'; ?>
            <?php    if (isset($params['id'])) {?>
                <div class="header-btn padding-left-twenty col-md-4 col-sm-4 padding-top-twenty">
                    <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $savetitle ?></button>
                </div>
            <?php } ?>
        <?php echo '</form>
        </div>
        </div>';
        }
        }
        ?>
        <script>
            function imasrubric_chgtype() {
                var val = document.getElementById("rubtype").value;

                els = document.getElementsByTagName("input");

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