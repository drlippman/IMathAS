<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Add Question', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<!--Get current time-->

<input type="hidden" class="" value="<?php echo $courseId = $course->id?>">
<?php $imasroot = AppUtility::getHomeURL().'img';?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $courseId]]); ?>
</div>
<!--Course name-->
<div class="title-container padding-bottom-two-em">
    <div class="row">
        <div class="col-sm-12 padding-right-zero">
            <div class=" col-sm-7" style="right: 30px;">
                <div class="vertical-align title-page">Add/Remove Questions
                    <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL().'question/question/add-question-help?section=addingquestionstoanassessment'?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i>
                    </a>
                </div>
            </div>
            <div class="col-sm-5 padding-right-zero" style="">
                <div class="floatright">
                    <div class="floatleft padding-right-fifteen"> <a style="" title="Preview this assessment" onclick="window.open('<?php echo AppUtility::getURLFromHome('assessment','assessment/show-test?cid='.$courseId.'&amp;id='.$assessmentId) ?>','Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20))" class="btn btn-primary page-settings"><img class="small-preview-icon" src="<?php echo AppUtility::getHomeURL().'img/prvAssess.png' ?>">&nbsp;&nbsp;Preview Assessment</a></div>
                    <div class="floatleft">
                        <a style="background-color: #008E71;border-color: #008E71;" title="Exit back to index page" href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$course->id) ?>" class="btn btn-primary  page-settings"><img class="small-icon" src="<?php echo AppUtility::getHomeURL().'img/done.png' ?>">&nbsp;Done</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if(isset($params['add-question-grid-ui']) || $params['modqs']) { ?>
    <div class="tab-content shadowBox padding-left-right-twenty padding-top-bottom-two-em">
  <?php  echo $addQuestionData; ?>
    </div>
<?php } else { ?>
    <div class="tab-content shadowBox">
    <?php if ($overwriteBody == AppConstant::NUMERIC_ONE) {
           echo $body; ?>
    <script>
                    var itemarray = <?php echo 0; ?>;
    </script>
          <?php } else { ?>
                <script type="text/javascript">
                    var j=jQuery.noConflict();
                    var curcid = <?php echo $courseId ?>;
                    var curaid = <?php echo $assessmentId ?>;
                    var defpoints = <?php echo $defpoints ?>;
                    var AHAHsaveurl = "<?php echo AppUtility::getURLFromHome('question','question/add-questions-save?cid='.$courseId.'&aid='.$assessmentId)?>";
                    var curlibs = '<?php echo $searchlibs;?>';
                </script>
    <div class="col-md-12 padding-zero">
        <?php
            echo '<div class="col-md-12 padding-left-zero" style="background-color: #E0E0E0;min-height: 60px;">
            <div class="padding-left-zero col-md-12 margin-top-twenty display-inline-block">
            <div class="col-md-2 display-inline-block">
                <a title="Modify assessment settings" href="'.AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$courseId.'&id='.$assessmentId).'">'.AppUtility::t("Assessment Settings",false).'
                </a>
            </div>
            <div class="col-md-2 display-inline-block">
                <a title="Categorize questions by outcome or other groupings" href="'.AppUtility::getURLFromHome('question','question/categorize?cid='.$courseId.'&aid='.$assessmentId).'">'.AppUtility::t("Categorize Questions",false).'
                </a>
            </div>
            <div class="col-md-2 display-inline-block">
                <a href="'.AppUtility::getURLFromHome('question','question/print-test?cid='.$courseId.'&aid='.$assessmentId).'">'.AppUtility::t("Create Print Version",false).'
                </a>
            </div>
            <div class="col-md-2 display-inline-block">
                <a title="Customize messages to display based on the assessment score" href="'.AppUtility::getURLFromHome('assessment','assessment/assessment-message?cid='.$courseId.'&aid='.$assessmentId).'">'.AppUtility::t("Define End Messages",false).'
                </a>
            </div>';
        if ($displaymethod=='VideoCue') {
            echo ' <div class="col-md-2">
                        <a title="Define Video Cues" href="'.AppUtility::getURLFromHome('question','question/add-video-times?cid='.$courseId.'&aid='.$assessmentId).'">'.AppUtility::t("Define Video Cues",false).'
                        </a>
                   </div>';
        }
        echo '</div></div>';
         ?>
    <div class="assessment-ques-shadowbox">
        <?php if ($beentaken) { ?>
            <div class="col-md-12">
                <h3>Warning</h3>
                <p>This assessment has already been taken.  Adding or removing questions, or changing a
                    question's settings (point value, penalty, attempts) now would majorly mess things up.
                    If you want to make these changes, you need to clear all existing assessment attempts
                </p>
                <p><input type=button value="Clear Assessment Attempts" onclick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&clearattempts=ask')  ?>'"></p>
            </div>
        <?php } ?>
        <?php if ($itemorder == '') { ?>
            <div class="col-md-8"><h3 class="margin-top-twenty-two">Questions in Assessment - <?php echo $pageAssessmentName ?></h3></div>
        <?php
                echo '<div class="col-md-12 margin-bottom-thirty">';
                echo "<p>No Questions currently in assessment</p>\n";
                echo '<a href="#" onclick="this.style.display=\'none\';document.getElementById(\'helpwithadding\').style.display=\'block\';return false;">';
                echo "<i class='margin-left-zero fa fa-question fa-fw help-icon'></i>";
                echo '<span class="margin-left-ten">How do I find questions to add?</span></a>';
                echo '<div id="helpwithadding" class="col-md-12 help-with-adding-question">';
                    if ($sessiondata['selfrom'.$assessmentId]=='lib') {
                        echo "<p>You are currently set to select questions from the question libraries.  If you would like to select questions from ";
                        echo "assessments you've already created, click the <b>Select From Assessments</b> button below</p>";
                        echo "<p>To find questions to add from the question libraries:";
                        echo "<ol><li>Click the <b>Select Libraries</b> link below to pop open the library selector</li>";
                        echo "<li>In the library selector, open up the topics of interest, and click the checkbox to select libraries to use</li>";
                        echo "<li>Scroll down in the library selector, and click the <b>Use Libraries</b> button</li> ";
                        echo "<li>On this page, click the <b>Search</b> button to list the questions in the libraries selected.<br/>  You can limit the listing by entering a sepecific search term in the box provided first, or leave it blank to view all questions in the chosen libraries</li>";
                        echo "</ol>";
                    } else if ($sessiondata['selfrom'.$assessmentId]=='assm') {
                        echo "<p>You are currently set t o select questions existing assessments.  If you would like to select questions from ";
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
            echo '</div>'; ?>

                <script>
                    var itemarray = <?php echo 0; ?>;
                </script>
      <?php  } else { ?>
                <form id="curqform" method="post" action="add-questions?modqs=true&aid=<?php echo $assessmentId ?>&cid=<?php echo $courseId ?>">
                <?php
                if (!$beentaken) {?>
                  <div class="col-md-12 padding-left-zero padding-top-twenty">  Check: <a href="#" onclick="return chkAllNone('curqform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('curqform','checked[]',false)">None</a></div>
                    <div class="col-md-12 padding-left-right-zero">
                        <div class="col-md-8 padding-left-zero display-inline-block">
                            <h3 class="margin-top-seventeen">Questions in Assessment - <?php echo $pageAssessmentName ?>
                            </h3>
                    </div>
                    <div class="col-md-2 pull-right display-inline-block width-two-hundred" style="left: 15px; margin-top: 10px; margin-bottom: 10px;">
                        <div class="with-selected ">
                            <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
                                <li class="dropdown">
                                    <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('With selected'); ?>
                                        <span class="caret right-aligned"></span>
                                    </a>
                                    <ul class="dropdown-menu with-selected">
                                        <li><a class="non-locked" href="javascript: removeSelected()"><i class="fa fa-trash-o fa-fw"></i>&nbsp;&nbsp;<?php AppUtility::t('Remove'); ?></a></li>
                                        <li><a class="non-locked" href="javascript: groupSelected()"><i class="fa fa-fw"></i>&nbsp;&nbsp;<?php AppUtility::t('Group'); ?></a></li>
                                        <li type="submit"><a href="javascript: changeSetting()"><i class="fa fa-fw"></i>&nbsp;&nbsp;<?php AppUtility::t('Change Settings'); ?></a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                    </div>
                <?php } ?>
                    <span id="submitnotice" style="color:red;"></span>
                    <div id="curqtbl"></div>
                </form>
                <div class="pull-right">Assessment points total: <span id="pttotal"></span></div>
                <script>
                    var itemarray = <?php echo $jsarr ?>;
                    var beentaken = <?php echo ($beentaken) ? 1:0; ?>;
                </script>
        <?php } ?>
    </div>
</div>
</div>
<?php if(!$beentaken){?>
<div class="tab-content shadowBox margin-top-fifteen">
    <?php
    /*
     * POTENTIAL QUESTIONS
     */
    if ($sessiondata['selfrom'.$assessmentId]=='lib') { //selecting from libraries
        if (!$beentaken) { ?>
            <form method=post action="add-questions?aid=<?php echo $assessmentId ?>&cid=<?php echo $courseId ?>">
                <div class="col-md-12 patential-ques-header">
                    <div class="col-md-3 padding-left-zero display-inline-block"><span id="libnames"> <?php echo 'In Libraries: '. $lnames ?></span></div>
                    <div class="col-md-2 display-inline-block padding-left-zero"><a href="javascript:GB_show('Library Select','<?php echo AppUtility::getHomeURL() ?>question/question/library-tree?libtree=popup&libs='+curlibs,500,500)"><?php AppUtility::t("Select Libraries") ?></a></div>
                    <div class="col-md-3 display-inline-block"><a  href="<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&selfrom=assm')  ?>"><?php AppUtility::t("Select From Assessment")?></a></div>
                    <input type=hidden name="libs" id="libs"  value="<?php echo $searchlibs ?>">

               </div>

            <div class="col-md-12 question-search">
                <div class="col-md-2 width-one-ninety display-inline-block">
                    <input type="text" rel="search" id="search" class="form-control" placeholder="Search" type=text size=15 name=search value="<?php echo $search ?>">
                </div>
                <div class="col-md-4 select-text-margin display-inline-block">
                        <span onmouseover="tipshow(this,'Search all libraries, not just selected ones')" onmouseout="tipout()">
                        <input type=checkbox name="searchall" value="1" <?php AppUtility::writeHtmlChecked($searchall,1,0) ?> />
                        Search all libs</span>
                        <span onmouseover="tipshow(this,'List only questions I own')" onmouseout="tipout()">
                        <input type=checkbox name="searchmine" value="1" <?php AppUtility::writeHtmlChecked($searchmine,1,0) ?> />
                        Mine only</span>
                        <span onmouseover="tipshow(this,'Exclude questions already in assessment')" onmouseout="tipout()">
                        <input type=checkbox name="newonly" value="1" <?php AppUtility::writeHtmlChecked($newonly,1,0) ?> />
                        Exclude added</span>
                </div>
                <div class="col-md-4 pull-right">
                    <div style="margin-right: -30px;float: right">
                        <span class=""><input type=submit value=Search></span>&nbsp;&nbsp;&nbsp;
                        <span class=""><input type=button value="Add New Question" onclick="window.location='<?php echo AppUtility::getURLFromHome('question','question/mod-data-set?aid='.$assessmentId.'&cid='.$courseId) ?>'"></span>
                    </div>
                </div>
            </div>
         </form>
            <div class="col-md-12">

                <?php
                if ($searchall==1 && trim($search)=='') {
                    echo "Must provide a search term when searching all libraries";
                } elseif (isset($search)) {
                    if ($noSearchResults) {
                        echo "<p>No Questions matched search</p>\n";
                    } else { ?>
                    <form id="selq" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>&addset=true">
                        <div class="col-md-12 display-inline-block">
                            <div class="col-md-6 right-fifteen display-inline-block padding-left-zero"><h3 class="margin-top-seven">Potential Questions</h3></div>
                            <div class="col-md-2 right-float left-fifteen margin-bottom-fifteen padding-right-zero width-two-hundred">
                                <input type="hidden" name="some_name">
                                <input type="hidden" name="add-question-name">
                                <div class="with-selected">
                                    <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
                                        <li class="dropdown">
                                            <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('With selected'); ?>
                                                <span class="caret right-aligned"></span>
                                            </a>
                                            <ul class="dropdown-menu with-selected potential-ques-dropdown">
                                                <li type="submit">
                                                    <a id="add-question" style=" name="add">
                                                        <i class="fa fa-plus padding-left-four"></i>&nbsp;
                                                        <span class="padding-left-five">
                                                            <?php AppUtility::t('Add'); ?>
                                                        </span>
                                                    </a>
                                                </li>
                                                <li type="submit">
                                                    <a id="add-question-default" name="addquick">
                                                        <i class="fa fa-cart-plus padding-left-two"></i>&nbsp;
                                                        <span class="padding-left-five">
                                                            <?php AppUtility::t('Add (using defaults)'); ?>
                                                        </span>
                                                    </a>
                                                </li>
                                                <li type="submit">
                                                    <a class="potentialques" href="javascript: previewsel('selq')">
                                                        <i class="fa fa-fw"></i>
                                                        <span class="padding-left-five">
                                                            <?php AppUtility::t('Preview Selected'); ?>
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <table cellpadding="5" id="myTable" class="potential-question-table " style="clear:both; position:relative;width: 100%">
                            <thead>
                            <tr><th class="questionId">
                                    <div class="checkbox override-hidden">
                                        <label>
                                            <input type="checkbox" name="header-checked"  value="">
                                            <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                                        </label>
                                    </div>
                                </th>
                                <th><?php AppUtility::t('Description') ?></th>
                                <th>&nbsp;</th>
                                <th><?php AppUtility::t('ID') ?></th>

                                <th><?php AppUtility::t('Type') ?></th>
                                <?php echo $pageLibRowHeader ?>
                                <th><?php AppUtility::t('Times Used') ?></th>
                                <?php if ($pageUseavgtimes) {?>
                                    <th><span onmouseover="tipshow(this,'Average time, in minutes, this question has taken students')" onmouseout="tipout()"><?php AppUtility::t('Avg Time') ?></span></th>
                                <?php } ?>
                                <th><?php AppUtility::t('Mine') ?></th>
                                <?php if ($searchall == AppConstant::NUMERIC_ZERO) { ?>
                                    <th><span onmouseover="tipshow(this,'Flag a question if it is in the wrong library')" onmouseout="tipout()"><?php AppUtility::t('Wrong Lib') ?></span></th>
                                <?php } ?>
                                <th style="width: 150px;"><?php AppUtility::t('Action') ?></th>
                                <th  style="width: 140px"></th>
                                <th></th>

                            </tr>
                            </thead>
                            <tbody id="potential-question-information-table">
                            <?php
                            $alt = AppConstant::NUMERIC_ZERO;
                            for ($j = AppConstant::NUMERIC_ZERO; $j<count($pageLibstouse); $j++) {

                                if ($searchall == AppConstant::NUMERIC_ZERO) {
                                    if ($alt == AppConstant::NUMERIC_ZERO) {
                                        echo "<tr class=even style='background-color: #f0f0f0;'>";
                                        $alt = AppConstant::NUMERIC_ONE;
                                    } else {
                                        echo "<tr class=odd>";
                                        $alt = AppConstant::NUMERIC_ZERO;
                                    }
                                    echo '<td></td>';
                                    echo '<td>';
                                    echo '<b>'.$lnamesarr[$pageLibstouse[$j]].'</b>';
                                    echo '</td>';
                                    for ($k = AppConstant::NUMERIC_ZERO; $k < AppConstant::NUMERIC_NINE; $k++) {
                                        echo '<td></td>';
                                    }
                                    echo '</tr>';
                                }

                                for ($i= AppConstant::NUMERIC_ZERO; $i<count($pageLibqids[$pageLibstouse[$j]]); $i++) {
                                    $qid =$pageLibqids[$pageLibstouse[$j]][$i];
                                    if ($alt == AppConstant::NUMERIC_ZERO) {
                                        echo "<tr class=even>";
                                        $alt = AppConstant::NUMERIC_ONE;
                                    } else {
                                        echo "<tr class=odd>";
                                        $alt = AppConstant::NUMERIC_ZERO;
                                    }
                                    ?>

                                    <td><div class="question-checkbox"><?php echo $pageQuestionTable[$qid]['checkbox'] ?></div></td>
                                    <td class="word-break-break-all"><?php echo $pageQuestionTable[$qid]['desc'] ?></td>
                                    <td class="nowrap">
                                       <div <?php if ($pageQuestionTable[$qid]['cap']) {echo 'class="ccvid"';}?>><?php echo $pageQuestionTable[$qid]['extref'] ?></div>
                                    </td>
                                    <td><?php echo $qid ?></td>

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
                                    <?php
                                    if ($pageUseavgtimes) {?><td class="c"><?php
                                        if (isset($pageQuestionTable[$qid]['qdata'])) {
                                            echo '<span onmouseover="tipshow(this,\'Avg score on first try: '.round($pageQuestionTable[$qid]['qdata'][0]).'%';
                                            echo '<br/>Avg time on first try: '.round($pageQuestionTable[$qid]['qdata'][1]/60,1).' min<br/>N='.$pageQuestionTable[$qid]['qdata'][2].'\')" onmouseout="tipout()">';
                                        } else {
                                            echo '<span>';
                                        }
                                        echo $pageQuestionTable[$qid]['avgtime'].'</span>'; ?></td> <?php }?>
                                    <td><?php echo $pageQuestionTable[$qid]['mine'] ?></td>
                                    <?php if ($searchall==0) {
                                        if ($pageQuestionTable[$qid]['junkflag']==1) {
                                            echo "<td class=c><img class=\"pointer\" id=\"tag{$pageQuestionTable[$qid]['libitemid']}\" src=\"$imasroot/flagfilled.gif\" onClick=\"toggleJunkFlag({$pageQuestionTable[$qid]['libitemid']});return false;\" /></td>";
                                        } else {
                                            echo "<td class=c><img class=\"pointer\" id=\"tag{$pageQuestionTable[$qid]['libitemid']}\" src=\"$imasroot/flagempty.gif\" onClick=\"toggleJunkFlag({$pageQuestionTable[$qid]['libitemid']});return false;\" /></td>";
                                        }
                                    } ?>
                                    <td class=""><div class="btn-group settings"> <a class='btn btn-primary disable-btn background-color-blue width-eighty-per'>
                                        <i class='fa fa-cog fa-fw'></i> Settings</a><a class='btn btn-primary dropdown-toggle width-twenty-per' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>
                                        <ul class='dropdown-menu min-width-hundred-per'>
                                        <li class=><?php echo $pageQuestionTable[$qid]['src'] ?></i></a></li>
                                        <li class=><?php echo $pageQuestionTable[$qid]['templ'] ?></i></a></li>
                                        </ul></div></td>
                                        <td><?php echo $pageQuestionTable[$qid]['preview'] ?></td>
                                        <td ><div class=''><?php echo $pageQuestionTable[$qid]['add'] ?>
                                        </div></td>
                                </tr>
                                <?php
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                        <p>Questions <span style="color:#999">in gray</span> have been added to the assessment.</p>
                        <script type="javascript">
                            initSortTable('myTable',Array(false,'S','N',false,'S',<?php echo ($searchall==1) ? "false, " : ""; ?>'N','S',false,false,false<?php echo ($searchall==0) ? ",false" : ""; ?>),true);
                        </script>
                    </form>
                    <?php
                    }
                }?>
            </div>
            <?php
        }
} else if ($sessiondata['selfrom'.$assessmentId]=='assm') { //select from assessments
    ?>
    <div style="margin: 15px;">

            <?php
        if (isset($params['achecked']) && (count($params['achecked'])==0)) {
            echo "<p>No Assessments Selected.  Select at least one assessment.</p>";
        } elseif (isset($sessiondata['aidstolist'.$assessmentId])) { //list questions
            ?>
            <form id="selq" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>&addset=true">
                <h3 style="padding-top: 10px;">Potential Questions</h3>
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
                        <th> </th><th>Description</th><th></th><th>ID<th>Preview</th><th>Type</th><th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th>
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

                <script type="javascript">
                    initSortTable('myTable',Array(false,'S','N',false,'S','N','S',false,false,false),true);
                </script>
            </form>

        <?php
        } else {  //choose assessments
            ?>

            <form id="sela" method=post action="add-questions?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>">
                <div class="patentialq-select-from-assessment-header">
                Check: <a href="#" onclick="return chkAllNone('sela','achecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('sela','achecked[]',false)">None</a>
                <input type=submit value="Use these Assessments" /> or
                <input type=button value="Select From Libraries" onClick="window.location='<?php echo AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId.'&selfrom=lib') ?>'">
                </div>
                <h3>Potential Questions</h3>
                <div><h4>Choose assessments to take questions from</h4></div>
                <table cellpadding=5 id=myTable class="floatleft question-table">
                    <thead >
                    <tr>
                        <th>
                            <div class="checkbox override-hidden">
                                <label>
                                    <input type="checkbox" name="potentialq-header-checked" value="">
                                    <span class="cr">
                                        <i class="cr-icon fa fa-check"></i>
                                    </span>
                                </label>
                            </div>
                        </th>
                        <th>Assessment</th>
                        <th>Summary</th>
                    </tr>
                    </thead>
                    <tbody id="potential-question-assessment-information-table">
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
                        <td class="question-check"><input type=checkbox name='achecked[]' value='<?php echo $pageAssessmentList[$i]['id'] ?>'></td>
                        <td><?php echo $pageAssessmentList[$i]['name'] ?></td>
                        <td><?php echo $pageAssessmentList[$i]['summary'] ?></td>
                        <?php echo "</tr>" ?>
        <?php
                    }
                    ?>

                    </tbody>
                </table>
                <script type="javascript">
                    initSortTable('myTable',Array(false,'S','S',false,false,false),true);
                </script>
            </form>

        <?php
        }
        }
        ?>
            <input type="hidden" id="address" value="<?php echo AppUtility::getURLFromHome('question','question/test-question?cid='.$courseId); ?>"/>
            <input type="hidden" id="junk-flag" value="<?php echo AppUtility::getURLFromHome('question','question/save-lib-assign-flag'); ?>"/>
            <script type="javascript">
                var previewqaddr = <?php echo AppUtility::getURLFromHome('question','question/test-question?cid='.$cid); ?>;
                var addqaddr = <?php echo $address; ?>;
                var JunkFlagsaveurl = <?php echo AppUtility::getURLFromHome('question','question/save-lib-assign-flag'); ?>;
            </script>
        <?php } ?>
    </div>
</div>

<?php } }?>