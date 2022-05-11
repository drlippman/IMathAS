<?php
//IMathAS: Question settings page, new assess format
//(c) 2019 David Lippman

/*** master php includes *******/
require "../init.php";
require "../includes/htmlutil.php";
require_once "../includes/TeacherAuditLog.php";

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Question Settings";

//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
    $overwriteBody = 1;
    $body = _("You need to log in as a teacher to access this page");
} else { //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

    $cid = Sanitize::courseId($_GET['cid']);
    $aid = Sanitize::onlyInt($_GET['aid']);

    if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
        $addq = 'addquestions2';
        $from = 'addq2';
    } else {
        $addq = 'addquestions';
        $from = 'addq';
    }
    $query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
    $query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':assessmentid' => $aid, ':courseid' => $cid));
    $beentaken = ($stm->rowCount() > 0);

    $curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">" . Sanitize::encodeStringForDisplay($coursename) . "</a> ";
    $curBreadcrumb .= "&gt; <a href=\"$addq.php?aid=$aid&cid=$cid\">" . _("Add/Remove Questions") . "</a> &gt; ";
    $curBreadcrumb .= _("Modify Question Settings");

    if (!empty($_GET['process'])) {
        if (isset($_GET['usedef'])) {
            $points = 9999;
            $attempts = 9999;
            $penalty = 9999;
            $regen = 0;
            $showans = 0;
            $rubric = 0;
            $showhints = -1;
            $showwork = -1;
            $fixedseeds = null;
            $extracredit = 0;
            $_POST['copies'] = 1;
        } else {
            if (trim($_POST['points']) == "") {$points = 9999;} else { $points = intval($_POST['points']);}
            if (trim($_POST['attempts']) == "" || intval($_POST['attempts']) <= 0) {
                $attempts = 9999;
            } else {
                $attempts = intval($_POST['attempts']);
            }

            if (trim($_POST['penalty']) == "") {$penalty = 9999;} else { $penalty = intval($_POST['penalty']);}
            if (trim($_POST['fixedseeds']) == "") {$fixedseeds = null;} else { $fixedseeds = trim($_POST['fixedseeds']);}
            if ($penalty != 9999) {
                $penalty_aftern = Sanitize::onlyInt($_POST['penalty_aftern']);
                if ($penalty_aftern > 1 && $attempts > 1) {
                    $penalty = 'S' . $penalty_aftern . $penalty;
                }
            }
            if (isset($_POST['allowregen'])) {
                $regen = Sanitize::onlyInt($_POST['allowregen']);
            } else {
                $regen = 0;
            }
            $showans = Sanitize::simpleASCII($_POST['showans']);
            $showwork = Sanitize::onlyInt($_POST['showwork']);
            $rubric = intval($_POST['rubric']);
            $showhints = intval($_POST['showhints']);
            $extracredit = !empty($_POST['ec']) ? 1 : 0;
        }
        if (isset($_GET['id'])) { //already have id - updating
            $stm = $DBH->prepare("SELECT * FROM imas_questions WHERE id=?");
            $stm->execute(array($_GET['id']));
            $old_settings = $stm->fetch(PDO::FETCH_ASSOC);
            if (isset($_POST['replacementid']) && $_POST['replacementid'] != '' && intval($_POST['replacementid']) != 0) {
                $query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans,showwork=:showwork,rubric=:rubric,showhints=:showhints,fixedseeds=:fixedseeds";
                $query .= ',questionsetid=:questionsetid,extracredit=:extracredit WHERE id=:id';
                $stm = $DBH->prepare($query);
                $settings = array(':points' => $points, ':attempts' => $attempts,
                    ':penalty' => $penalty, ':regen' => $regen, ':showans' => $showans, ':showwork' => $showwork,
                    ':rubric' => $rubric, ':showhints' => $showhints, ':fixedseeds' => $fixedseeds,
                    ':questionsetid' => $_POST['replacementid'], ':extracredit' => $extracredit, 
                    ':id' => $_GET['id']);
                $stm->execute($settings);
            } else {
                $query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans, showwork=:showwork, rubric=:rubric,showhints=:showhints,fixedseeds=:fixedseeds,extracredit=:extracredit";
                $query .= " WHERE id=:id";
                $stm = $DBH->prepare($query);
                $settings = array(':points' => $points, ':attempts' => $attempts,
                    ':penalty' => $penalty, ':regen' => $regen, ':showans' => $showans, ':showwork' => $showwork,
                    ':rubric' => $rubric, ':showhints' => $showhints, ':fixedseeds' => $fixedseeds, ':extracredit' => $extracredit,
                    ':id' => $_GET['id']);
                $stm->execute($settings);
            }
            $changes = array();
            foreach ($old_settings as $k => $v) {
                if (isset($settings[':' . $k]) && $settings[':' . $k] != $v) {
                    $changes[$k] = ['old' => $v, 'new' => $settings[':' . $k]];
                }
            }
            if ($stm->rowCount() > 0 && $beentaken && count($changes) > 0) {
                TeacherAuditLog::addTracking(
                    $cid,
                    "Question Settings Change",
                    $_GET['id'],
                    $changes
                );
            }
            if (isset($_POST['copies']) && $_POST['copies'] > 0) {
                $stm = $DBH->prepare("SELECT questionsetid FROM imas_questions WHERE id=:id");
                $stm->execute(array(':id' => $_GET['id']));
                $_GET['qsetid'] = $stm->fetchColumn(0);
            }
        }
        require_once "../includes/updateptsposs.php";
        if (isset($_GET['qsetid'])) { //new - adding
            $stm = $DBH->prepare("SELECT itemorder,defpoints FROM imas_assessments WHERE id=:id");
            $stm->execute(array(':id' => $aid));
            list($itemorder, $defpoints) = $stm->fetch(PDO::FETCH_NUM);
            for ($i = 0; $i < $_POST['copies']; $i++) {
                $query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,showwork,questionsetid,rubric,showhints,fixedseeds,extracredit) ";
                $query .= "VALUES (:assessmentid, :points, :attempts, :penalty, :regen, :showans, :showwork, :questionsetid, :rubric, :showhints, :fixedseeds, :extracredit)";
                $stm = $DBH->prepare($query);
                $stm->execute(array(':assessmentid' => $aid, ':points' => $points, ':attempts' => $attempts, ':penalty' => $penalty, ':regen' => $regen,
                    ':showans' => $showans, ':showwork' => $showwork, ':questionsetid' => $_GET['qsetid'], ':rubric' => $rubric, ':showhints' => $showhints, ':fixedseeds' => $fixedseeds, ':extracredit' => $extracredit));
                $qid = $DBH->lastInsertId();

                //add to itemorder
                if (isset($_GET['id'])) { //am adding copies of existing
                    $itemarr = explode(',', $itemorder);
                    $key = array_search($_GET['id'], $itemarr);
                    array_splice($itemarr, $key + 1, 0, $qid);
                    $itemorder = implode(',', $itemarr);
                } else {
                    if ($itemorder == '') {
                        $itemorder = $qid;
                    } else {
                        $itemorder = $itemorder . ",$qid";
                    }
                }
            }
            $stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
            $stm->execute(array(':itemorder' => $itemorder, ':id' => $aid));

            updatePointsPossible($aid, $itemorder, $defpoints);
        } else {
            updatePointsPossible($aid);
        }

        // Delete any teacher or tutor attempts on this assessment
        $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
      imas_teachers AS usr ON usr.userid=iar.userid AND usr.courseid=?
      WHERE iar.assessmentid=?';
        $stm = $DBH->prepare($query);
        $stm->execute(array($cid, $aid));
        $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
      imas_tutors AS usr ON usr.userid=iar.userid AND usr.courseid=?
      WHERE iar.assessmentid=?';
        $stm = $DBH->prepare($query);
        $stm->execute(array($cid, $aid));

        // retotal student assessment records with changes
        require_once '../assess2/AssessHelpers.php';
        AssessHelpers::retotalAll($cid, $aid);

        header('Location: ' . $GLOBALS['basesiteurl'] . "/course/$addq.php?cid=$cid&aid=$aid&r=" . Sanitize::randomQueryStringParam());
        exit;
    } else { //DEFAULT DATA MANIPULATION

        if (isset($_GET['id'])) {
            $stm = $DBH->prepare("SELECT points,attempts,penalty,regen,showans,showwork,rubric,showhints,questionsetid,fixedseeds,extracredit FROM imas_questions WHERE id=:id");
            $stm->execute(array(':id' => $_GET['id']));
            $line = $stm->fetch(PDO::FETCH_ASSOC);
            if ($line['penalty'][0] === 'S') {
                $penalty_aftern = $line['penalty'][1];
                $line['penalty'] = substr($line['penalty'], 2);
            } else {
                $penalty_aftern = 1;
            }

            if ($line['points'] == 9999) {$line['points'] = '';}
            if ($line['attempts'] == 9999) {$line['attempts'] = '';}
            if ($line['penalty'] == 9999) {$line['penalty'] = '';}
            if ($line['fixedseeds'] === null) {$line['fixedseeds'] = '';}
            $qsetid = $line['questionsetid'];
        } else {
            //set defaults
            $line['points'] = "";
            $line['attempts'] = "";
            $line['penalty'] = "";
            $line['fixedseeds'] = '';
            $penalty_aftern = 1;
            $line['regen'] = 0;
            $line['showans'] = '0';
            $line['showwork'] = -1;
            $line['rubric'] = 0;
            $line['showhints'] = -1;
            $line['extracredit'] = 0;
            $qsetid = $_GET['qsetid'];
        }

        $stm = $DBH->prepare("SELECT description FROM imas_questionset WHERE id=:id");
        $stm->execute(array(':id' => $qsetid));
        $qdescrip = $stm->fetchColumn(0);
        if (isset($_GET['loc'])) {
            $qdescrip = $_GET['loc'] . ': ' . $qdescrip;
        }
        $qingroup = (strpos($_GET['loc'],'-') !== false);

        $rubric_vals = array(0);
        $rubric_names = array('None');
        $stm = $DBH->prepare("SELECT id,name FROM imas_rubrics WHERE ownerid IN (SELECT userid FROM imas_teachers WHERE courseid=:cid) OR groupid=:groupid ORDER BY name");
        $stm->execute(array(':cid' => $cid, ':groupid' => $groupid));
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $rubric_vals[] = $row[0];
            $rubric_names[] = $row[1];
        }
        if ($beentaken) {
            $page_beenTakenMsg = "<p><strong>" . _("Warning") . "</strong>: ";
            $page_beenTakenMsg .= _("This assessment has already been taken.  Altering the points or penalty may cause some temporary weirdness for student currently working on the assessment. ");
            $page_beenTakenMsg .= _("If you want to add additional copies of this question, you should clear all existing assessment attempts") . ".</p> ";
            $page_beenTakenMsg .= "<p><input type=button value=\"" . _("Clear Assessment Attempts") . "\" onclick=\"window.location='$addq.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
        } else {
            $page_beenTakenMsg = '';
        }

        //get defaults
        $query = "SELECT defpoints,defattempts,defpenalty,defregens,";
        $query .= "showans,showwork,submitby,showhints,shuffle FROM imas_assessments ";
        $query .= "WHERE id=:id";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':id' => $aid));
        $defaults = $stm->fetch(PDO::FETCH_ASSOC);
        $defaults['showwork'] = ($defaults['showwork'] & 3);

        if ($defaults['defpenalty'][0] === 'S') {
            $defaults['penalty'] = sprintf(_('%d%% after %d full-credit tries'),
                substr($defaults['defpenalty'], 2), $defaults['defpenalty'][1]);
        } else {
            $defaults['penalty'] = $defaults['defpenalty'] . '%';
        }

        if ($defaults['showans'] == 'after_lastattempt') {
            $defaults['showans'] = _('After last attempt on a version');
        } else if ($defaults['showans'] == 'after_take') {
            $defaults['showans'] = _('After assessment version is submitted');
        } else if ($defaults['showans'] == 'never') {
            $defaults['showans'] = _('Never during assessment');
        } else if (substr($defaults['showans'], 0, 5) == 'after') {
            $defaults['showans'] = sprintf(_('After %d tries'), substr($defaults['showans'], 6));
        }
        if ($defaults['showhints'] == 0) {
            $defaults['showhints'] = _('No');
        } else if ($defaults['showhints'] == 1) {
            $defaults['showhints'] = _('Hints');
        } else if ($defaults['showhints'] == 2) {
            $defaults['showhints'] = _('Video/text buttons');
        } else if ($defaults['showhints'] == 3) {
            $defaults['showhints'] = _('Hints and Video/text buttons');
        }

        if ($defaults['showwork'] == 0) {
            $defaults['showwork'] = _('No');
        } else if ($defaults['showwork'] == 1) {
            $defaults['showwork'] = _('During Assessment');
        } else if ($defaults['showwork'] == 2) {
            $defaults['showwork'] = _('After assessment');
        } else if ($defaults['showwork'] == 3) {
            $defaults['showwork'] = _('During or after assessment');
        }
    }
}

/******* begin html output ********/
$placeinhead = '<script type="text/javascript">
function previewq(qn) {
  previewpop = window.open(imasroot+"/course/testquestion2.php?fixedseeds=1&cid="+cid+"&qsetid="+qn,"Testing","width="+(.4*screen.width)+",height="+(.8*screen.height)+",scrollbars=1,resizable=1,status=1,top=20,left="+(.6*screen.width-20));
  previewpop.focus();
}
</script>';
require "../header.php";

if ($overwriteBody == 1) {
    echo $body;
} else {
    ?>
	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div>
	<?php echo $page_beenTakenMsg; ?>

<div id="headermodquestion" class="pagetitle"><h1>
<?php
if (isset($_GET['id'])) {
        echo _('Modify Question Settings');
    } else {
        echo _('New Question Settings');
    }
    ?>
</h1></div>
<p><?php

echo '<b>' . Sanitize::encodeStringForDisplay($qdescrip) . '</b> ';
    echo '<button type="button" onclick="previewq(' . Sanitize::encodeStringForJavascript($qsetid) . ')">' . _('Preview') . '</button>';
    ?>
</p>
<form method=post action="modquestion2.php?process=true&<?php echo "cid=$cid&aid=" . Sanitize::encodeUrlParam($aid) . "&from=$from";if (isset($_GET['id'])) {echo "&id=" . Sanitize::encodeUrlParam($_GET['id']);}if (isset($_GET['qsetid'])) {echo "&qsetid=" . Sanitize::encodeUrlParam($_GET['qsetid']);} ?>">
<p><?php echo _("Leave items blank to use the assessment's default values."); ?>
<input type="submit" value="<?php echo _('Save Settings'); ?>"></p>
<?php
if (!isset($_GET['id']) || $beentaken) {
        ?>
<span class=form><?php echo _("Points for this problem:"); ?></span>
<span class=formright> <input type=text size=4 name=points value="<?php echo Sanitize::encodeStringForDisplay($line['points']); ?>"><br/><i class="grey"><?php echo _("Default:"); ?> <?php echo Sanitize::encodeStringForDisplay($defaults['defpoints']); ?></i></span><BR class=form>
<?php
} else {
        echo '<input type="hidden" name="points" value="' . Sanitize::encodeStringForDisplay($line['points']) . '"/>';
    }
    ?>
<span class=form><?php echo _("Tries allowed on each version of this problem:"); ?></span>
<span class=formright> <input type=text size=3 name=attempts value="<?php echo Sanitize::encodeStringForDisplay($line['attempts']); ?>">
  <br/><i class="grey"><?php echo _("Default:"); ?> <?php echo Sanitize::encodeStringForDisplay($defaults['defattempts']); ?></i>
</span><BR class=form>

<span class=form><?php echo _("Penalty on Tries:"); ?></span>
<span class=formright><input type=text size=2 name=penalty value="<?php echo Sanitize::encodeStringForDisplay($line['penalty']); ?>">
  <?php echo sprintf(_('%% per try after %s full-credit tries'),
        '<input type=text size=1 name="penalty_aftern" value="' . Sanitize::encodeStringForDisplay($penalty_aftern) . '">'); ?>
   <br/><i class="grey"><?php echo _('Default:'); ?> <?php echo Sanitize::encodeStringForDisplay($defaults['penalty']); ?></i>
</span><BR class=form>
<?php
// TODO: Add regen penalty stuff.  Do we really want to?
    if ($defaults['submitby'] == 'by_question' && $defaults['defregens'] > 1) {
        ?>
<span class="form"><?php echo _('Allow &quot;Try similar problem&quot;?'); ?></span>
<span class=formright>
    <select name="allowregen">
     <option value="0" <?php if ($line['regen'] == 0) {echo 'selected="1"';}?>><?php echo _('Use Default'); ?></option>
     <option value="1" <?php if ($line['regen'] > 0) {echo 'selected="1"';}?>><?php echo _('No'); ?></option>
</select><br/><i class="grey"><?php echo _('Default:'); ?> <?php echo $defaults['defregens']; ?> <?php echo _('versions'); ?></i></span><br class="form"/>
<?php
}
    ?>
<span class=form><?php echo _('Show Answers during Assessment'); ?></span><span class=formright>
    <select name="showans">
     <option value="0" <?php if ($line['showans'] == '0') {echo 'selected="1"';}?>><?php echo _('Use Default'); ?></option>
     <option value="N" <?php if ($line['showans'] == 'N') {echo 'selected="1"';}?>><?php echo _('Never during assessment'); ?></option>
    </select><br/><i class="grey"><?php echo _('Default:'); ?> <?php echo Sanitize::encodeStringForDisplay($defaults['showans']); ?></i></span><br class="form"/>

<span class=form><?php echo _('Provide "Show Work" boxes'); ?></span><span class=formright>
    <select name="showwork">
     <option value="-1" <?php if ($line['showwork'] == '-1') {echo 'selected="1"';}?>><?php echo _('Use Default'); ?></option>
     <option value="0" <?php if ($line['showwork'] == '0') {echo 'selected="1"';}?>><?php echo _('No'); ?></option>
     <option value="1" <?php if ($line['showwork'] == '1') {echo 'selected="1"';}?>><?php echo _('During Assessment'); ?></option>
     <option value="2" <?php if ($line['showwork'] == '2') {echo 'selected="1"';}?>><?php echo _('After assessment'); ?></option>
     <option value="3" <?php if ($line['showwork'] == '3') {echo 'selected="1"';}?>><?php echo _('During or after assessment'); ?></option>
   </select><br/><i class="grey"><?php echo _('Default:'); ?> <?php echo Sanitize::encodeStringForDisplay($defaults['showwork']); ?></i></span><br class="form"/>

<span class=form><?php echo _('Show hints and video/text buttons?'); ?></span><span class=formright>
    <select name="showhints">
     <option value="-1" <?php if ($line['showhints'] == -1) {echo 'selected="1"';}?>><?php echo _('Use Default'); ?></option>
     <option value="0" <?php if ($line['showhints'] == 0) {echo 'selected="1"';}?>><?php echo _('No'); ?></option>
     <option value="1" <?php if ($line['showhints'] == 1) {echo 'selected="1"';}?>><?php echo _('Hints'); ?></option>
     <option value="2" <?php if ($line['showhints'] == 2) {echo 'selected="1"';}?>><?php echo _('Video/text buttons'); ?></option>
     <option value="3" <?php if ($line['showhints'] == 3) {echo 'selected="1"';}?>><?php echo _('Hints and Video/text buttons'); ?></option>
    </select><br/><i class="grey"><?php echo _('Default:'); ?> <?php echo $defaults['showhints']; ?></i></span><br class="form"/>

<span class=form><?php echo _('Use Scoring Rubric'); ?></span><span class=formright>
<?php
writeHtmlSelect('rubric', $rubric_vals, $rubric_names, $line['rubric']);
    echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=modq&amp;aid=" . Sanitize::encodeUrlParam($aid) . "&amp;qid=" . Sanitize::encodeUrlParam($_GET['id']) . "\">" . _("Add new rubric") . "</a> ";
    echo "| <a href=\"addrubric.php?cid=$cid&amp;from=modq&amp;aid=" . Sanitize::encodeUrlParam($aid) . "&amp;qid=" . Sanitize::encodeUrlParam($_GET['id']) . "\">" . _("Edit rubrics") . "</a> ";
    ?>
    </span><br class="form"/>
<?php
if (!$qingroup) {
?>
<span class="form"><label for="ec"><?php echo _('Count as extra credit?');?></label></span>
<span class=formright>
    <input type=checkbox value=1 name=ec id=ec <?php if ($line['extracredit'] > 0) { echo 'checked';} ?>>
</span><br class="form"/>
<?php
}
if (isset($_GET['qsetid'])) { //adding new question
        echo "<span class=form>" . _("Number of copies of question to add:") . "</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
    } else if (!$beentaken) {
        echo "<span class=form>" . _("Number, if any, of additional copies to add to assessment:") . "</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
    }
    if ($line['fixedseeds'] == '') {
        echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'.advanced\').show();return false">' . _('Advanced') . '</a></span><br class="form"/>';
    }
    echo '<div class="advanced" id="fixedseedwrap" ';
    if ($line['fixedseeds'] == '') {
        echo 'style="display:none;"';
    }
    echo '>';
    echo '<span class="form">' . _('Restricted question seed list:') . '</span>';
    echo '<span class="formright"><input size=30 name="fixedseeds" id="fixedseeds" value="' . $line['fixedseeds'] . '"/></span><br class="form"/>';
    echo '</div>';
    if ($line['fixedseeds'] != '' && isset($_GET['id'])) {
        echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'.advanced\').show();return false">' . _('Advanced') . '</a></span><br class="form"/>';
    }
    if (isset($_GET['id'])) {
        echo '<div class="advanced" style="display:none">';
        echo '<span class="form">' . _('Replace this question with question ID:') . ' ';
        if ($beentaken) {
            echo '<br/><span class=noticetext>' . _('WARNING: This is NOT recommended. It will mess up the question for any student who has already attempted it, and any work they have done may look garbled when you view it') . '</span>';
        }
        echo '</span><span class="formright"><input size="7" name="replacementid"/></span><br class="form"/>';
        echo '</div>';
    }

    echo '<div class="submit"><input type="submit" value="' . _('Save Settings') . '"></div>';
    echo '</form>';
}

require "../footer.php";
?>
