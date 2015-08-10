<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Add Question', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print' />
<!--Get current time-->
<input type="hidden" class="" value="<?php echo $courseId = $course->id?>">
<?php $imasroot = AppUtility::getHomeURL();?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $courseId]]); ?>
</div>
<!--Course name-->

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php AppUtility::t('Add/Remove Questions') ?></div>
        </div>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid='.$course->id); ?>"
               class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Setting</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']); ?>
</div>
<div class="tab-content shadowBox">
<?php    if ($overwriteBody==1) {
    echo $body;
    } else {
?>
    <script type="text/javascript">
        var curcid = <?php echo $courseId ?>;
        var curaid = <?php echo $assessmentId ?>;
        var defpoints = <?php echo $defpoints ?>;
        var AHAHsaveurl = "<?php echo AppUtility::getURLFromHome('question','question/save-Questions?cid='.$courseId.'&aid='.$assessmentId)?>";
        var curlibs = '<?php echo $searchlibs;?>';
    </script>

    <div class="breadcrumb"><?php echo $curBreadcrumb ?></div>
    <div id="headeraddquestions" class="pagetitle"><h2>Add/Remove Questions
            <img src="<?php echo AppUtility::getAssetURL(); ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo AppUtility::getAssetURL() ?>docs/help.php?section=addingquestionstoanassessment','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
        </h2></div>
    <?php
    echo '<div class="cp"><a href="'.AppUtility::getURLFromHome('assessment','assessment/add-assessment?id='.$params['aid'].'&amp;cid='.$course->id ).'">'._('Assessment Settings').'</a></div>';
    if ($beentaken) {
    ?>
        <h3>Warning</h3>
        <p>This assessment has already been taken.  Adding or removing questions, or changing a
            question's settings (point value, penalty, attempts) now would majorly mess things up.
            If you want to make these changes, you need to clear all existing assessment attempts
        </p>
        <p><input type=button value="Clear Assessment Attempts" onclick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&clearattempts=ask')  ?>'">
        </p>
    <?php } ?>
    <h3>Questions in Assessment - <?php echo $pageAssessmentName ?></h3>

<?php
if ($itemorder == '') {
    echo "<p>No Questions currently in assessment</p>\n";

    echo '<a href="#" onclick="this.style.display=\'none\';document.getElementById(\'helpwithadding\').style.display=\'block\';return false;">';
    echo "<img src='".AppUtility::getAssetURL()."/img/help.gif' /> ";
    echo 'How do I find questions to add?</a>';
    echo '<div id="helpwithadding" style="display:none">';
    if ($sessiondata['selfrom'.$assessmentId]=='lib') {
        echo "<p>You are currently set to select questions from the question libraries.  If you would like to select questions from ";
        echo "assessments you've already created, click the <b>Select From Assessments</b> button below</p>";
        echo "<p>To find questions to add from the question libraries:";
        echo "<ol><li>Click the <b>Select Libraries</b> button below to pop open the library selector</li>";
        echo " <li>In the library selector, open up the topics of interest, and click the checkbox to select libraries to use</li>";
        echo " <li>Scroll down in the library selector, and click the <b>Use Libraries</b> button</li> ";
        echo " <li>On this page, click the <b>Search</b> button to list the questions in the libraries selected.<br/>  You can limit the listing by entering a sepecific search term in the box provided first, or leave it blank to view all questions in the chosen libraries</li>";
        echo "</ol>";
    } else if ($sessiondata['selfrom'.$assessmentId]=='assm') {
        echo "<p>You are currently set to select questions existing assessments.  If you would like to select questions from ";
        echo "the question libraries, click the <b>Select From Libraries</b> button below</p>";
        echo "<p>To find questions to add from existing assessments:";
        echo "<ol><li>Use the checkboxes to select the assessments you want to pull questions from</li>";
        echo " <li>Click <b>Use these Assessments</b> button to list the questions in the assessments selected</li>";
        echo "</ol>";
    }
    echo "<p>To select questions and add them:</p><ul>";
    echo " <li>Click the <b>Preview</b> button after the question description to view an example of the question</li>";
    echo " <li>Use the checkboxes to mark the questions you want to use</li>";
    echo " <li>Click the <b>Add</b> button above the question list to add the questions to your assessment</li> ";
    echo "  </ul>";
    echo '</div>';

} else {
?>
    <form id="curqform" method="post" action="addquestions.php?modqs=true&aid=<?php echo $assessmentId ?>&cid=<?php echo $courseId ?>">
        <?php
        if (!$beentaken) {?>

            Check: <a href="#" onclick="return chkAllNone('curqform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('curqform','checked[]',false)">None</a>

            With Selected: <input type=button value="Remove" onclick="removeSelected()" />
            <input type=button value="Group" onclick="groupSelected()" />
            <input type="submit" value="Change Settings" />

        <?php
        }
        ?>
        <span id="submitnotice" style="color:red;"></span>
        <div id="curqtbl"></div>

    </form>
    <p>Assessment points total: <span id="pttotal"></span></p>
    <script>
        var itemarray = <?php echo $jsarr ?>;
        var beentaken = <?php echo ($beentaken) ? 1:0; ?>;
        document.getElementById("curqtbl").innerHTML = generateTable();
    </script>
<?php
}
    if ($displaymethod=='VideoCue') {
        echo '<p><input type=button value="Define Video Cues" onClick="window.location='.AppUtility::getURLFromHome('question','question/add-video-times?cid='.$courseId.'&aid='.$assessmentId).'"/></p>';
    }
    ?>

    <p>
        <input type=button value="Done" title="Exit back to course page" onClick="window.location='<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId) ?>'">
        <input type=button value="Assessment Settings" title="Modify assessment settings" onClick="window.location='<?php echo AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$courseId.'&id='.$assessmentId) ?>'">
        <input type=button value="Categorize Questions" title="Categorize questions by outcome or other groupings" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/categorize?cid='.$courseId.'&aid='.$assessmentId) ?>'">
        <input type=button value="Create Print Version" onClick="window.location='<?php echo AppUtility::getURLFromHome('assessment','assessment/print-test?cid='.$courseId.'&aid='.$assessmentId) ?>'">
        <input type=button value="Define End Messages" title="Customize messages to display based on the assessment score" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/assess-end-msg?cid='.$courseId.'&aid='.$assessmentId) ?>'">
        <input type=button value="Preview" title="Preview this assessment" onClick="window.open('<?php echo AppUtility::getURLFromHome('assessment','assessment/show-assessment?cid='.$courseId.'&id='.$assessmentId);?>','Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20))">
    </p>

    <?php
    //<input type=button value="Select Libraries" onClick="libselect()">

    //POTENTIAL QUESTIONS
    if ($sessiondata['selfrom'.$assessmentId]=='lib') { //selecting from libraries
    if (!$beentaken) {
        ?>

    <h3>Potential Questions</h3>
    <form method=post action="add-questions?aid=<?php echo $assessmentId ?>&cid=<?php echo $courseId ?>">

        In Libraries:
        <span id="libnames"><?php echo $lnames ?></span>
        <input type=hidden name="libs" id="libs"  value="<?php echo $searchlibs ?>">
        <input type="button" value="Select Libraries" onClick="GB_show('Library Select','<?php echo AppUtility::getHomeURL() ?>question/question/library-tree?libtree=popup&libs='+curlibs,500,500)" />
        or <input type=button value="Select From Assessments" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&selfrom=assm')  ?>'">
        <br>
        Search:
        <input type=text size=15 name=search value="<?php echo $search ?>">
		<span onmouseover="tipshow(this,'Search all libraries, not just selected ones')" onmouseout="tipout()">
		<input type=checkbox name="searchall" value="1" <?php AppUtility::writeHtmlChecked($searchall,1,0) ?> />
		Search all libs</span>
		<span onmouseover="tipshow(this,'List only questions I own')" onmouseout="tipout()">
		<input type=checkbox name="searchmine" value="1" <?php AppUtility::writeHtmlChecked($searchmine,1,0) ?> />
		Mine only</span>
		<span onmouseover="tipshow(this,'Exclude questions already in assessment')" onmouseout="tipout()">
		<input type=checkbox name="newonly" value="1" <?php AppUtility::writeHtmlChecked($newonly,1,0) ?> />
		Exclude added</span>
        <input type=submit value=Search>
        <input type=button value="Add New Question" onclick="window.location='<?php echo AppUtility::getURLFromHome('question','question/mod-data-set?aid='.$assessmentId.'&cid='.$courseId) ?>'">

    </form>
    <?php
if ($searchall==1 && trim($search)=='') {
    echo "Must provide a search term when searching all libraries";
} elseif (isset($search)) {
if ($noSearchResults) {
    echo "<p>No Questions matched search</p>\n";
} else {
    ?>
    <form id="selq" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>&addset=true">

        Check: <a href="#" onclick="return chkAllNone('selq','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('selq','nchecked[]',false)">None</a>
        <input name="add" type=submit value="Add" />
        <input name="addquick" type=submit value="Add (using defaults)">
        <input type=button value="Preview Selected" onclick="previewsel('selq')" /><br><br>
        <table cellpadding="5" id="myTable" class="" style="clear:both; position:relative;">
            <thead>
            <tr><th>&nbsp;</th><th>Description</th><th>&nbsp;</th><th>ID</th><th>Preview</th><th>Type</th>
                <?php echo $pageLibRowHeader ?>
                <th>Times Used</th>
                <?php if ($pageUseavgtimes) {?><th><span onmouseover="tipshow(this,'Average time, in minutes, this question has taken students')" onmouseout="tipout()">Avg Time</span></th><?php } ?>
                <th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th>
                <?php if ($searchall==0) { echo '<th><span onmouseover="tipshow(this,\'Flag a question if it is in the wrong library\')" onmouseout="tipout()">Wrong Lib</span></th>';} ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $alt=0;
            for ($j=0; $j<count($pageLibstouse); $j++) {

                if ($searchall==0) {
                    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                    echo '<td></td>';
                    echo '<td>';
                    echo '<b>'.$lnamesarr[$pageLibstouse[$j]].'</b>';
                    echo '</td>';
                    for ($k=0;$k<9;$k++) {echo '<td></td>';}
                    echo '</tr>';
                }

                for ($i=0;$i<count($pageLibqids[$pageLibstouse[$j]]); $i++) {
                    $qid =$pageLibqids[$pageLibstouse[$j]][$i];
                    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                    ?>

					<td><?php echo $pageQuestionTable[$qid]['checkbox'] ?></td>
					<td><?php echo $pageQuestionTable[$qid]['desc'] ?></td>
					<td class="nowrap">
					   <div <?php if ($pageQuestionTable[$qid]['cap']) {echo 'class="ccvid"';}?>><?php echo $pageQuestionTable[$qid]['extref'] ?></div>
					</td>
					<td><?php echo $qid ?></td>
					<td><?php echo $pageQuestionTable[$qid]['preview'] ?></td>
					<td><?php echo $pageQuestionTable[$qid]['type'] ?></td>
<?php
                    if ($searchall==1) {
                        ?>
                        <td><?php echo $pageQuestionTable[$qid]['lib'] ?></td>
                    <?php
                    }
                    ?>
					<td class=c><?php
                    echo $pageQuestionTable[$qid]['times']; ?>
					</td>
					<?php if ($pageUseavgtimes) {?><td class="c"><?php
                        if (isset($pageQuestionTable[$qid]['qdata'])) {
                            echo '<span onmouseover="tipshow(this,\'Avg score on first try: '.round($pageQuestionTable[$qid]['qdata'][0]).'%';
                            echo '<br/>Avg time on first try: '.round($pageQuestionTable[$qid]['qdata'][1]/60,1).' min<br/>N='.$pageQuestionTable[$qid]['qdata'][2].'\')" onmouseout="tipout()">';
                        } else {
                            echo '<span>';
                        }
                        echo $pageQuestionTable[$qid]['avgtime'].'</span>'; ?></td> <?php }?>
					<td><?php echo $pageQuestionTable[$qid]['mine'] ?></td>
					<td class=c><?php echo $pageQuestionTable[$qid]['add'] ?></td>
					<td><?php echo $pageQuestionTable[$qid]['src'] ?></td>
					<td class=c><?php echo $pageQuestionTable[$qid]['templ'] ?></td>
					<?php if ($searchall==0) {
                        if ($pageQuestionTable[$qid]['junkflag']==1) {
                            echo "<td class=c><img class=\"pointer\" id=\"tag{$pageQuestionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$pageQuestionTable[$qid]['libitemid']});return false;\" /></td>";
                        } else {
                            echo "<td class=c><img class=\"pointer\" id=\"tag{$pageQuestionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$pageQuestionTable[$qid]['libitemid']});return false;\" /></td>";
                        }
                    } ?>
				</tr>
<?php
                }
            }
            ?>
            </tbody>
        </table>
        <p>Questions <span style="color:#999">in gray</span> have been added to the assessment.</p>
        <script type="text/javascript">
            initSortTable('myTable',Array(false,'S','N',false,'S',<?php echo ($searchall==1) ? "false, " : ""; ?>'N','S',false,false,false<?php echo ($searchall==0) ? ",false" : ""; ?>),true);
        </script>
    </form>

<?php
}
}
}

} else if ($sessiondata['selfrom'.$assessmentId]=='assm') { //select from assessments
    ?>

    <h3>Potential Questions</h3>

    <?php
if (isset($params['achecked']) && (count($params['achecked'])==0)) {
    echo "<p>No Assessments Selected.  Select at least one assessment.</p>";
} elseif (isset($sessiondata['aidstolist'.$assessmentId])) { //list questions
    ?>
    <form id="selq" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>&addset=true">

        <input type=button value="Select Assessments" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&clearassmt=1') ?>'">
        or <input type=button value="Select From Libraries" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&selfrom=lib') ?>'">
        <br/>

        Check: <a href="#" onclick="return chkAllNone('selq','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('selq','nchecked[]',false)">None</a>
        <input name="add" type=submit value="Add" />
        <input name="addquick" type=submit value="Add Selected (using defaults)">
        <input type=button value="Preview Selected" onclick="previewsel('selq')" />

        <table cellpadding=5 id=myTable class=gb>
            <thead>
            <tr>
                <th> </th><th>Description</th><th></th><th>ID</td><th>Preview</th><th>Type</th><th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alt=0;
            for ($i=0; $i<count($pageAssessmentQuestions['desc']);$i++) {
                if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                ?>
				<td></td>
				<td><b><?php echo $pageAssessmentQuestions['desc'][$i] ?></b></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
<?php
                for ($x=0;$x<count($pageAssessmentQuestions[$i]['desc']);$x++) {
                    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                    ?>
				<td><?php echo $pageAssessmentQuestions[$i]['checkbox'][$x] ?></td>
				<td><?php echo $pageAssessmentQuestions[$i]['desc'][$x] ?></td>
				<td class="nowrap">
				  <div <?php if ($pageAssessmentQuestions[$i]['cap'][$x]) {echo 'class="ccvid"';}?>><?php echo $pageAssessmentQuestions[$i]['extref'][$x] ?></div>
				</td>
				<td><?php echo $pageAssessmentQuestions[$i]['qsetid'][$x] ?></td>
				<td><?php echo $pageAssessmentQuestions[$i]['preview'][$x] ?></td>
				<td><?php echo $pageAssessmentQuestions[$i]['type'][$x] ?></td>
				<td class=c><?php echo $pageAssessmentQuestions[$i]['times'][$x] ?></td>
				<td><?php echo $pageAssessmentQuestions[$i]['mine'][$x] ?></td>
				<td class=c><?php echo $pageAssessmentQuestions[$i]['add'][$x] ?></td>
				<td class=c><?php echo $pageAssessmentQuestions[$i]['src'][$x] ?></td>
				<td class=c><?php echo $pageAssessmentQuestions[$i]['templ'][$x] ?></td>
			</tr>

<?php
                }
            }
            ?>
            </tbody>
        </table>

        <script type="text/javascript">
            initSortTable('myTable',Array(false,'S','N',false,'S','N','S',false,false,false),true);
        </script>
    </form>

<?php
} else {  //choose assessments
    ?>
    <h4>Choose assessments to take questions from</h4>
    <form id="sela" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>">
        Check: <a href="#" onclick="return chkAllNone('sela','achecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('sela','achecked[]',false)">None</a>
        <input type=submit value="Use these Assessments" /> or
        <input type=button value="Select From Libraries" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&selfrom=lib') ?>'">
<br><br>
        <table cellpadding=5 id=myTable class=>
            <thead >
            <tr><th></th><th>Assessment</th><th>Summary</th></tr>
            </thead>
            <tbody>
            <?php

            $alt=0;
            for ($i=0;$i<count($pageAssessmentList);$i++) {
                if ($alt==0) {
                    echo "<tr class=even>";
                    $alt=1;
                } else {
                    echo "<tr class=odd>";
                    $alt=0;
                }
                ?>
				<td><input type=checkbox name='achecked[]' value='<?php echo $pageAssessmentList[$i]['id'] ?>'></td>
				<td><?php echo $pageAssessmentList[$i]['name'] ?></td>
				<td><?php echo $pageAssessmentList[$i]['summary'] ?></td>
				<?php echo "</tr>" ?>
<?php
            }
            ?>

            </tbody>
        </table>
        <script type=\"text/javascript\">
			initSortTable('myTable',Array(false,'S','S',false,false,false),true);
		</script>
    </form>

<?php
}

}
?>



<?php } ?>
</div>