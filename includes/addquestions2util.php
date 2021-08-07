<?php

// Get existing questions in assessment as json
// If called with data, include itemorder, showhints, showwork, intro
function getQuestionsAsJSON($cid, $aid, $data=null)
{   
    global $DBH, $userid, $groupid, $adminasteacher, $aver;

    if ($data === null) {
        $stm = $DBH->prepare("SELECT itemorder,showhints,showwork,intro FROM imas_assessments WHERE id=:id");
        $stm->execute(array(':id' => $aid));
        $data = $stm->fetch(PDO::FETCH_ASSOC);
        $data['showwork'] = ($data['showwork'] & 3);
    }
    $ln = 1;

    // Format of imas_assessments.intro is a JSON representation like
    // [ "original (main) intro text",
    //  { displayBefore:  question number to display before,
    //    displayUntil:  last question number to display it for
    //    text:  the actual text to show
    //    ispage: is this is a page break (0 or 1)
    //    pagetitle: page title text
    //  },
    //  ...
    // ]
    $text_segments = array();
    $introconvertmsg = '';
    if (($introjson = json_decode($data['intro'], true)) !== null) { //is json intro
        //$text_segments = array_slice($introjson,1); //remove initial Intro text
        for ($i = 0; $i < count($introjson); $i++) {
            if (isset($introjson[$i]['displayBefore'])) {
                if (!isset($text_segments[$introjson[$i]['displayBefore']])) {
                    $text_segments[$introjson[$i]['displayBefore']] = array();
                }
                $text_segments[$introjson[$i]['displayBefore']][] = $introjson[$i];
            }
        }
    } else {
        if (strpos($data['intro'], '[Q ') !== false || strpos($data['intro'], '[QUESTION ') !== false) {
            $introconvertmsg = '<p>' . sprintf(_('It appears this assessment is using an older [Q #] or [QUESTION #] tag. You can %sconvert that into a new format%s if you would like.'), '<a href="convertintro.php?cid=' . $cid . '&aid=' . $aid . '">', '</a>') . '</p>';
        }
    }

    $grp0Selected = "";
    if (isset($_SESSION['groupopt' . $aid])) {
        $grp = $_SESSION['groupopt' . $aid];
        $grp1Selected = ($grp == 1) ? " selected" : "";
    } else {
        $grp = 0;
        $grp0Selected = " selected";
    }

    $questionjsarr = array();
    $existingq = array();
    $query = "SELECT iq.id,iq.questionsetid,iqs.description,iqs.userights,iqs.ownerid,";
    $query .= "iqs.qtype,iq.points,iq.withdrawn,iqs.extref,imas_users.groupid,iq.showhints,";
    $query .= "iq.showwork,iq.rubric,iqs.solution,iqs.solutionopts,iqs.meantime,iqs.meanscore,";
    $query .= "iqs.meantimen FROM imas_questions AS iq ";
    $query .= "JOIN imas_questionset AS iqs ON iqs.id=iq.questionsetid JOIN imas_users ON iqs.ownerid=imas_users.id ";
    $query .= "WHERE iq.assessmentid=:aid";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':aid' => $aid));
    while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($line === false) {continue;} //this should never happen, but avoid issues if it does
        $existingq[] = $line['questionsetid'];
        //output item array
        if ($line['userights'] > 3 || ($line['userights'] == 3 && $line['groupid'] == $groupid) || $line['ownerid'] == $userid || $adminasteacher) { //can edit without template?
            $canedit = 1;
        } else {
            $canedit = 0;
        }
        $extrefval = 0;
        if ($aver > 1) {
            if (($line['showhints'] == -1 && ($data['showhints'] & 2) == 2) ||
                ($line['showhints'] > -1 && ($line['showhints'] & 2) == 2)
            ) {
                $extrefval += 1;
            }
        } else {
            if (($line['showhints'] == 0 && $data['showhints'] == 1) || $line['showhints'] == 2) {
                $extrefval += 1;
            }
        }
        if ($line['extref'] != '') {
            $extref = explode('~~', $line['extref']);
            $hasvid = false;
            $hasother = false;
            $hascap = false;
            foreach ($extref as $v) {
                if (strtolower(substr($v, 0, 5)) == "video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                    $hasvid = true;
                    if (strpos($v, '!!1') !== false) {
                        $hascap = true;
                    }
                } else {
                    $hasother = true;
                }
            }
            //$page_questionTable[$i]['extref'] = '';
            if ($hasvid) {
                $extrefval += 4;
            }
            if ($hasother) {
                $extrefval += 2;
            }
            if ($hascap) {
                $extrefval += 16;
            }
        }
        if ($line['solution'] != '' && ($line['solutionopts'] & 2) == 2) {
            $extrefval += 8;
        }
        if (($line['showwork'] == -1 && $data['showwork'] > 0) || $line['showwork'] > 0) {
            $extrefval += 32;
        }
        if ($line['rubric'] > 0) {
            $extrefval += 64;
        }

        $timeout = array();
        $timeout[0] = round($line['meantime'] / 60, 1);
        $timeout[1] = round($line['meanscore'], 1);
        $timeout[2] = round($line['meantime'] / 60, 1);
        $timeout[3] = intval($line['meantimen']);

        $questionjsarr[$line['id']] = array((int) $line['id'],
            (int) $line['questionsetid'],
            Sanitize::encodeStringForDisplay($line['description']),
            Sanitize::encodeStringForDisplay($line['qtype']),
            (int) Sanitize::onlyInt($line['points']),
            (int) $canedit,
            (int) Sanitize::onlyInt($line['withdrawn']),
            (int) $extrefval,
            $timeout,
        );

    }

    $apointstot = 0;
    $qncnt = 0;

    $jsarr = array();
    if ($data['itemorder'] != '') {
        $items = explode(",", $data['itemorder']);
    } else {
        $items = array();
    }
    for ($i = 0; $i < count($items); $i++) {
        if (isset($text_segments[$qncnt])) {
            foreach ($text_segments[$qncnt] as $text_seg) {
                //stupid hack: putting a couple extra unused entries in array so length>=5
                $jsarr[] = array("text", $text_seg['text'],
                    Sanitize::onlyInt($text_seg['displayUntil'] - $text_seg['displayBefore'] + 1),
                    Sanitize::onlyInt($text_seg['ispage']),
                    $text_seg['pagetitle'],
                    isset($text_seg['forntype']) ? $text_seg['forntype'] : 0);
            }
        }
        if (strpos($items[$i], '~') !== false) {
            $subs = explode('~', $items[$i]);
            if (isset($_COOKIE['closeqgrp-' . $aid]) && in_array("$i", explode(',', $_COOKIE['closeqgrp-' . $aid]))) {
                $closegrp = 0;
            } else {
                $closegrp = 1;
            }
            $qsdata = array();
            for ($j = (strpos($subs[0], '|') === false) ? 0 : 1; $j < count($subs); $j++) {
                if (!isset($questionjsarr[$subs[$j]])) {continue;} //should never happen
                $qsdata[] = $questionjsarr[$subs[$j]];
            }
            if (count($qsdata) == 0) {continue;} //should never happen
            if (strpos($subs[0], '|') === false) { //for backwards compat
                $jsarr[] = array(1, 0, $qsdata, $closegrp);
                $qncnt++;
            } else {
                $grpparts = explode('|', $subs[0]);
                $jsarr[] = array((int) Sanitize::onlyInt($grpparts[0]),
                    (int) Sanitize::onlyInt($grpparts[1]),
                    $qsdata,
                    (int) $closegrp);
                $qncnt += $grpparts[0];
            }
        } else {
            if (!isset($questionjsarr[$items[$i]])) {continue;} //should never happen
            $jsarr[] = $questionjsarr[$items[$i]];
            $qncnt++;
        }
    }
    if (isset($text_segments[$qncnt])) {
        foreach ($text_segments[$qncnt] as $j => $text_seg) {
            //stupid hack: putting a couple extra unused entries in array so length>=5
            $jsarr[] = array("text", $text_seg['text'],
                Sanitize::onlyInt($text_seg['displayUntil'] - $text_seg['displayBefore'] + 1),
                Sanitize::onlyInt($text_seg['ispage']),
                $text_seg['pagetitle'], 1);
        }
    }

    return array($jsarr, $existingq, $introconvertmsg);
}
