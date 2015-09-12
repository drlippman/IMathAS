<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
/*** pre-html data manipulation, including function code *******/
//set some page specific variables and counters
//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
if ($from=='modq') {
    $returnstr = 'modquestion.php?cid='.$cid.'&amp;aid='.$_GET['aid'].'&amp;id='.$_GET['qid'];
    $curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Question Settings</a> ";
} else if ($from=='addg') {
    $returnstr = 'addgrades.php?cid='.$cid.'&amp;gbitem='.$_GET['gbitem'].'&amp;grades=all';
    $curBreadcrumb .= "&gt; <a href=\"$returnstr\">Offline Grades</a> ";
} else if ($from=='addf') {
    $returnstr = 'addforum.php?cid='.$cid.'&amp;id='.$_GET['fid'];
    $curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Forum</a> ";
}
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
<!--    <div class=breadcrumb>--><?php //echo $curBreadcrumb ?><!--</div>-->
    <div id="headeraddforum" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
    <?php
    if (!isset($params['id'])) {  //displaying "Manage Rubrics" page ?>
         <p>Select a rubric to edit or <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id=new')?>">Add a new rubric</a></p><p>
        <?php
        foreach($rubricsName as $rubricName){
            echo "{$rubricName['name']}" ?>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id='.$rubricName['id'].$fromstr);?> ">Edit</a><br/>
        <?php }
        echo '</p>';
    } else {  //adding/editing a rubric
        /*  Rubric Types
        *   1: score breakdown (record score and feedback)
        *   0: score breakdown (record score)
        *   3: score total (record score and feedback)
        *   4: score total (record score only)
        *   2: record feedback only (checkboxes)
        */
        $rubtypeval = array(1,0,3,4,2);
        $rubtypelabel = array('Score breakdown, record score and feedback','Score breakdown, record score only','Score total, record score and feedback','Score total, record score only','Feedback only'); ?>
         <form method="post" action="add-rubric?cid=<?php echo $course->id ?>&id=<?php echo $params['id']?><?php echo $fromstr?>">
        <?php echo '<p>Name:  <input type="text" size="70" name="rubname" value="'.str_replace('"','\\"',$rubname).'"/></p>';

        echo '<p>Rubric Type: ';
        AssessmentUtility::writeHtmlSelect('rubtype',$rubtypeval,$rubtypelabel,$rubtype,null,null,'onchange="imasrubric_chgtype()"');
        echo '</p>';

        echo '<p>Share with Group: <input type="checkbox" name="rubisgroup" '.AssessmentUtility::getHtmlChecked($rubgrp,-1,1).' /></p>';
        echo '<table><thead><tr><th>Rubric Item<br/>Shows in feedback</th><th>Instructor Note<br/>Not in feedback</th><th><span id="pointsheader" ';
        if ($rubtype==2) {echo 'style="display:none;" ';}
        if ($rubtype==3 || $rubtype==4) {
            echo '>Percentage of score</span>';
        } else {
            echo '>Percentage of score<br/>Should add to 100</span>';
        }
        echo '</th></tr></thead><tbody>';
        for ($i=0;$i<15; $i++) {
            echo '<tr><td><input type="text" size="40" name="rubitem'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][0])) { echo str_replace('"','&quot;',$rubric[$i][0]);}
            echo '"/></td>';
            echo '<td><input type="text" size="40" name="rubnote'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][1])) { echo str_replace('"','&quot;',$rubric[$i][1]);}
            echo '"/></td>';
            echo '<td><input type="text" size="4" class="rubricpoints" ';
            if ($rubtype==2) {echo 'style="display:none;" ';}
            echo 'name="rubscore'.$i.'" value="';
            if (isset($rubric[$i]) && isset($rubric[$i][2])) { echo str_replace('"','&quot;',$rubric[$i][2]);} else {echo 0;}
            echo '"/></td></tr>';
        }
        echo '</table>';
        echo '<input type="submit" value="'.$savetitle.'"/>';
        echo '</form>';
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