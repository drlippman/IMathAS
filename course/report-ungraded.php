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
    //zero: score=0 and lastchange > 0
    //nofb: status&8 = 0
    //mg: need to decode scoreddata, look for questions with raw = -2 with 
    //    no override and no feedback 
    $qarr = [$cid];
    $query = 'SELECT ia.id,ia.name,istu.userid,iar.score,iar.lastchange,iar.status,iu.FirstName,iu.LastName,ia.submitby';
    if ($feature == 'mg' || $feature == 'work' || $feature == 'qwork') {
        $query .= ',iar.scoreddata';
    }
    $query .= ' FROM imas_assessments AS ia 
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
        if ($feature == 'zero') {
            if ($row['score'] == 0 && $row['lastchange'] > 0) {
                addtolist($row);
            }
        } else if ($feature == 'nofb') {
            if (($row['status']&8) == 0 && $row['lastchange'] > 0) {
                addtolist($row);
            }
        } else if ($feature == 'work' && ($row['status']&8) == 8) {
            // has feedback somewhere, so doesn't meet the "no feedback" condition
            continue;
        } else {
            if ($row['lastchange'] > 0) {
                
                $data = json_decode(Sanitize::gzexpand($row['scoreddata']), true);
                if ($data !== null) {
                    // which versions to consider?
                    // for now: all versions of full assessment, latest question version
                    // last try only
                    $addtxt = '';
                    // work: looking for any work field with no feedback
                    // qwork: looking for a work field with no question-level feedback
                    foreach ($data['assess_versions'] as $an=>$aver) {
                        foreach ($aver['questions'] as $qn=>$q) {
                            $lastver = count($q['question_versions']) - 1;
                            $qver = $q['question_versions'][$lastver];
                            if ($feature == 'work') {
                                if (!empty($qver['work'])) {
                                    // found some work, we already know no feedback, so report it out
                                    addtolist($row);
                                    continue 3; // continue to next row
                                }
                            } else if ($feature == 'qwork') {
                                if (!empty($qver['work']) && empty($qver['feedback'])) {
                                    if ($row['submitby'] == 'by_assessment') {
                                        $addtxt .= sprintf(_('(Question %d of Attempt %d)'), $qn+1, $an+1);
                                    } else {
                                        $addtxt .= sprintf(_('(Question %d)'), $qn+1, $an+1);
                                    }
                                }
                            } else if ($feature == 'mg') {
                                if (!isset($qver['tries'])) { continue; }
                                foreach ($qver['tries'] as $pn=>$part) {
                                    $lasttry = count($part) - 1;
                                    if ($part[$lasttry]['raw'] == -2 && 
                                        !isset($qver['scoreoverride'][$pn]) &&
                                        empty($qver['feedback'])
                                    ) {
                                        if ($row['submitby'] == 'by_assessment') {
                                            $addtxt .= sprintf(_('(Question %d of Attempt %d)'), $qn+1, $an+1);
                                        } else {
                                            $addtxt .= sprintf(_('(Question %d)'), $qn+1, $an+1);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($addtxt !== '') {
                        addtolist($row, $addtxt);
                    }
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

$pagetitle = _('Assessments To Grade');

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

echo '<p>'._('This page can help you find assignments you need to manually grade.').'</p>';

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
echo ' <li><label><input type=radio name=feature value=zero checked />' . _('A score of zero') . '</label>';
echo ' <li><label><input type=radio name=feature value=nofb />' . _('No feedback (assessment or question level)') . '</label>';
echo ' <li><label><input type=radio name=feature value=mg />' . _('A manual grade question with a score of 0 and no question-level feedback') . '</label>';
echo ' <li><label><input type=radio name=feature value=work />' . _('Assessment has work added, but no feedback (assessment or question level)') . '</label>';
echo ' <li><label><input type=radio name=feature value=qwork />' . _('Question has work added, but no question-level feedback') . '</label>';
echo '</ul>';
echo '<p><button type=button onclick="getresults()">'._('Search').'</button>';
echo '<span id=spinner style="display:none;"><img alt="" src="../img/updating.gif"/></span></p>';

echo '<div id="results"></div>';

require_once '../footer.php';
