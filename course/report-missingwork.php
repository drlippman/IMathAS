<?php

require_once "../init.php";

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
}

if (isset($_POST['cat'])) {
    $cat = intval($_POST['cat']);
    $feature = Sanitize::simpleString($_POST['feature']);
    $avail = intval($_POST['avail']);
    // allq: check for work on all questions that allow work
    // none: check for work somewhere
    
    // first, get showwork setting for questions in assessment
    $query = 'SELECT ia.id AS aid,iq.id,iq.showwork FROM imas_questions AS iq 
        JOIN imas_assessments AS ia ON ia.id=iq.assessmentid
        WHERE ia.courseid=?';
    $stm = $DBH->prepare($query);
    $stm->execute([$cid]);
    $questionShowwork = [];
    $hasQuestionShowwork = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['showwork']>0) {
            $hasQuestionShowwork[$row['aid']] = true;
        }
        $questionShowwork[$row['id']] = $row['showwork'];
    }

    $qarr = [$cid];
    $query = 'SELECT ia.id,ia.name,istu.userid,iar.score,iar.lastchange,iar.status,iu.FirstName,iu.LastName,
        ia.submitby,ia.showwork,iar.scoreddata 
        FROM imas_assessments AS ia 
        JOIN imas_assessment_records AS iar ON iar.assessmentid=ia.id
        JOIN imas_users AS iu ON iar.userid=iu.id
        JOIN imas_students AS istu ON iar.userid=istu.userid AND ia.courseid=istu.courseid
        WHERE ia.courseid=? AND (ia.submitby="by_question" OR iar.status&64=64)';
    if ($_POST['cat'] !== "all") {
        $query .= ' AND ia.gbcategory=?';
        $qarr[] = $cat;
    }
    if ($avail == 0) {
        $query .= ' AND ia.enddate < ?';
        $qarr[] = time();
    }
    $query .= ' ORDER BY ia.name,iu.LastName';
    $stm = $DBH->prepare($query);
    $stm->execute($qarr);
    $foundlist = [];
    function addtolist($d, $addtxt = '') {
        global $cid, $foundlist;
        $out = '<li><a href="../assess2/gbviewassess.php?cid='.$cid.'&aid='.$d['id'].'&uid='.$d['userid'].'" target="_blank">';
        $out .= sprintf('%s for %s, %s', $d['name'], $d['LastName'], $d['FirstName']);
        $out .= ' '.$addtxt;
        $out .= '</li>';
        $foundlist[] = $out;
    }
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if (($row['showwork']&3)==0 && empty($hasQuestionShowwork[$row['id']])) {
            // no work required; skip
            continue;
        }
        if ($row['lastchange'] > 0) {
            $data = json_decode(Sanitize::gzexpand($row['scoreddata']), true);
            if ($data !== null) {
                // which versions to consider?
                // for now: scored version only
                $qwithwork = 0;
                $aver = $data['assess_versions'][$data['scored_version']];
                foreach ($aver['questions'] as $qn=>$q) {
                    $qver = $q['question_versions'][$q['scored_version']];
                    if ((($row['showwork']&3)>0 && $questionShowwork[$qver['qid']] != 0) || 
                        $questionShowwork[$qver['qid']] > 0
                    ) {
                        // showwork is on for assessment and not disabled at question level,
                        // or showwork enabled at question level
                        if (!empty($qver['work'])) {
                            if ($feature == 'none') {
                                // we found work somewhere, and that's what we were looking for,
                                // so this assessment record is fine
                                continue 2; // continue to next row
                            }
                        } else {
                            if ($feature == 'allq') {
                                // we found a question without work, which is enough to flag this 
                                // assessment
                                addtolist($row);
                                continue 2; // continue to next row
                            }
                        }
                    }
                }
                if ($feature == 'none') {
                    // never found work
                    addtolist($row);
                }
            }
        }
    }
    echo implode('', $foundlist);
    exit;
}

$stm = $DBH->prepare('SELECT id,name FROM imas_gbcats WHERE courseid=? ORDER BY name');
$stm->execute([$cid]);
$gbcats = $stm->fetchAll(PDO::FETCH_KEY_PAIR);

$pagetitle = _('Assessments Missing Work');

$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">" . _('Course Reports') . "</a> ";


require_once "../header.php";
?>
<script>
    function getresults() {
        $("#results").empty();
        $("#spinner").show();
        $("#statusmsg").text(_('Searching'));
        let cat = $("#cat").val();
        let avail = $("#avail").val();
        let feature = $('input[name="feature"]:checked').val();
        $.post({
            url: window.location.href,
            data: {cat: cat, avail:avail, feature: feature},
            dataType: "html"
        }).done(function(data) {
            if (data == '') {
                $("#results").html('<?php echo _('No Results'); ?>');
            } else {
                $("#results").html('<ul>'+data+'</ul>');
            }
            
        }).always(function() {
            $("#spinner").hide();
            $("#statusmsg").text(_('Done'));
        })
    }
</script>
<?php
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

echo '<p>'._('This page can help you find assignments missing work.').'</p>';

echo '<p><label>'._('Find submitted assignments in gradebook category');
echo ' <select id=cat>';
echo ' <option value="all" selected>' . _('All categories') . '</option>';
echo ' <option value="0">' . _('Default') . '</option>';
foreach ($gbcats as $id=>$cat) {
    echo '<option value="'.intval($id).'">'.Sanitize::encodeStringForDisplay($cat).'</option>';
}
echo '</select></label><br/><label>' . _('with availability');
echo ' <select id=avail>';
echo ' <option value="0" selected>' . _('Past Due') . '</option>';
echo ' <option value="1">' . _('Past Due and Available') . '</option>';
echo '</select><label><br/>';
echo _('having this feature:') . '</p>';
echo '<ul class=nomark>';
echo ' <li><label><input type=radio name=feature value=none checked />' . _('No work submitted anywhere (work on one question is sufficient)') . '</label>';
echo ' <li><label><input type=radio name=feature value=allq />' . _('No work submitted on a question that allows it (work required on every question)') . '</label>';
echo '</ul>';
echo '<p><button type=button onclick="getresults()">'._('Search').'</button>';
echo '<span id=spinner style="display:none;"><img alt="" src="../img/updating.gif"/></span></p>';

echo '<div id="results"></div>';

require_once '../footer.php';
