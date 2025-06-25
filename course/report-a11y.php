<?php

require_once "../init.php";
require_once "../includes/videodata.php";

$what = 'cid';

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
}
if (isset($_GET['scan']) && $_GET['scan'] === 'myqs') {
    $what = 'myqs';
} 

$errors = [];
$vidids = [];
$vidlocs = [];
$asciisvgpattern = '/^showasciisvg\(\s*((("([^"\\\\]|\\\\.)*"|\'([^\'\\\\]|\\\\.)*\'|[^,()])+,\s*){0,2}("([^"\\\\]|\\\\.)*"|\'([^\'\\\\]|\\\\.)*\'|[^,()])?\s*)?\)$/';
function a11yscan($content, $field, $type, $itemname, $link='') {
    global $asciisvgpattern,$vidids,$vidlocs;
    // ensure regex considers \" as well as " to account for encoding
    $content = str_replace(['\\\'','\\"', "'"], ['"','"','"'], $content);
    // look for empty text, or missing alt text.  sloppy, but works
    if (preg_match('/(<img[^>]*?alt="(.*?)"[^>]*?>|<img[^>]*>)/', $content, $matches)) {
        if (!isset($matches[2])) { // used second pattern; missing alt text
            adderror(_('Missing alt text'), $field, $type, $itemname, $link); 
        } else if (trim($matches[2]) == '') {
            adderror(_('Blank alt text'), $field, $type, $itemname, $link); 
        }
    }
    // look for asciisvg call with undefined alt text.
    // we'll assume if alt text is defined but blank that it was intentional
    // does not account for use of replacealttext later in the question
    if (preg_match($asciisvgpattern, $content)) {
        adderror(_('Likely useless auto-generated alt text from showasciisvg'), $field, $type, $itemname, $link); 
    }
    // look for youtube videos
    if (preg_match_all('/((youtube\.com|youtu\.be)[^>]*?)"/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            if (($vidid = getvideoid($m[1])) !== '') {
                $vidids[] = $vidid;
                $vidlocs[$vidid] = [$field, $type, $itemname, $link];
            }
        }
    }
}
function adderror($descr, $loc, $itemtype, $itemname, $link) {
    global $errors;
    if ($itemtype !== null) {
        $errors[] = [sprintf('%s in %s of %s %s', $descr, $loc, $itemtype, $itemname), $link];
    } else {
        $errors[] = [sprintf('%s in %s', $descr, $loc), $link];
    }
}

if ($what === 'cid') {
    // scan assessment summary, intro (including between-question text)
    $stm = $DBH->prepare("SELECT name,summary,intro,id FROM imas_assessments WHERE courseid=?");
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['summary'], _('Summary'), _('Assessment'), $row['name'], "course/addassessment2.php?cid=$cid&id=" . $row['id']);
        a11yscan($row['intro'], _('Intro or Between-question text'), _('Assessment'), $row['name'], "course/addassessment2.php?cid=$cid&id=" . $row['id']);
    }

    // scan inline text summary
    $stm = $DBH->prepare("SELECT title,text,id FROM imas_inlinetext WHERE courseid=?");
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['text'], _('Text'), _('Inline text item'), $row['title'], "course/addinlinetext.php?cid=$cid&id=" . $row['id']);
    }

    // scan linked text summary, text
    $stm = $DBH->prepare("SELECT title,summary,text,id FROM imas_linkedtext WHERE courseid=?");
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['summary'], _('Summary'), _('Link item'), $row['title'], "course/addlinkedtext.php?cid=$cid&id=" . $row['id']);
        a11yscan($row['text'], _('Text'), _('Link item'), $row['title'], "course/addlinkedtext.php?cid=$cid&id=" . $row['id']);
    }

    // scan forum summary, postinstr, replyinstr
    $stm = $DBH->prepare("SELECT name,description,postinstr,replyinstr,id FROM imas_forums WHERE courseid=?");
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['description'], _('Description'), _('Forum'), $row['name'], "course/addforum.php?cid=$cid&id=" . $row['id']);
        a11yscan($row['postinstr'], _('Post Instructions'), _('Forum'), $row['name'], "course/addforum.php?cid=$cid&id=" . $row['id']);
        a11yscan($row['replyinstr'], _('Reply Instructions'), _('Forum'), $row['name'], "course/addforum.php?cid=$cid&id=" . $row['id']);
    }

    // scan forum post message, for sticky forum posts with type>0
    $stm = $DBH->prepare("SELECT ifp.subject,ifp.message,ifs.name FROM imas_forums AS ifs 
        JOIN imas_forum_posts AS ifp ON ifs.id=ifp.forumid AND ifp.posttype>0 WHERE ifs.courseid=?");
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['message'], _('Sticky post') . ' ' . $row['subject'], _('Forum'), $row['name']);
    }

    // scan questionset control, qtext
    $query = 'SELECT iqs.control,iqs.qtext,ia.name,iqs.id,iqs.extref FROM imas_questionset AS iqs 
        JOIN imas_questions AS iq ON iqs.id=iq.questionsetid
        JOIN imas_assessments AS ia ON ia.id=iq.assessmentid
        WHERE ia.courseid=?';
    $stm = $DBH->prepare($query);
    $stm->execute([$cid]);
    $extrefissues = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['control'] . $row['qtext'], sprintf(_('Question ID %d'), $row['id']), 
            _('Assessment'), $row['name'], "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']);
        if (preg_match('/youtu[^!]*!!0/', $row['extref'])) {
            $extrefissues[] = $row;
            /*
            adderror(_('Uncaptioned video'), sprintf(_('Question ID %d'), $row['id']), 
                _('Assessment'), $row['name'], "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']); 
            */
        }
    }

    // get uncaptioned videos 
    $vidstocheck=[];
    foreach ($extrefissues as $row) {
        $extrefs = explode('~~', $row['extref']);
        foreach ($extrefs as $v) {
            $parts = explode('!!', $v);
            if (count($parts)>2 && $parts[2] == 0) { // not captioned
                $vidid = getvideoid($parts[1]);
                if ($vidid !== '') {
                    $vidstocheck[] = $vidid;
                }
            }
        }
    }
    // pull caption data from database for those videos
    if (count($vidstocheck)>0) {
        $vidstocheck = array_values(array_unique($vidstocheck));
        $ph = Sanitize::generateQueryPlaceholders($vidstocheck);
        $stm = $DBH->prepare("SELECT vidid,captioned FROM imas_captiondata WHERE vidid IN ($ph) AND status>0 AND status<3");
        $stm->execute($vidstocheck);
        $captiondata = $stm->fetchAll(PDO::FETCH_KEY_PAIR);
        // now loop back over the extref issues and see if we have updated caption info
        foreach ($extrefissues as $row) {
            $gaveerrorthisquestion = false;
            $updatedextref = false;
            $extrefs = explode('~~', $row['extref']);
            //loop over each extref
            foreach ($extrefs as $k=>$v) {
                $parts = explode('!!', $v);
                if (count($parts)>2 && $parts[2] == 0) { // not captioned, but might not be video
                    $vidid = getvideoid($parts[1]);
                    if ($vidid !== '' && !empty($captiondata[$vidid])) {
                        // it's a video, and we know it's captioned from captiondata database
                        // update extref
                        $parts[2] = 1;
                        $extrefs[$k] = implode('!!', $parts);
                        $updatedextref = true;
                    } else if ($vidid !== '' && !$gaveerrorthisquestion) {
                        // it's a video, don't have captions, give error once
                        adderror(_('Uncaptioned video'), sprintf(_('Question ID %d'), $row['id']), 
                            _('Assessment'), $row['name'], "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']);
                        $gaveerrorthisquestion = true;
                    }
                }
            }
            // if we updated extrefs, update database
            if ($updatedextref) {
                $stm = $DBH->prepare("UPDATE imas_questionset SET extref=? WHERE id=?");
                $stm->execute([implode('~~', $extrefs), $row['id']]);
            }
        }
    }

    // scan qimages alttext for blank
    $query = 'SELECT iqi.alttext,iqi.var,ia.name,iqs.id FROM imas_questionset AS iqs 
        JOIN imas_qimages AS iqi ON iqi.qsetid=iqs.id
        JOIN imas_questions AS iq ON iqs.id=iq.questionsetid
        JOIN imas_assessments AS ia ON ia.id=iq.assessmentid
        WHERE ia.courseid=?';
    $stm = $DBH->prepare($query);
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if (trim($row['alttext']) == '') {
            adderror(_('Blank alt text'), sprintf(_('Question ID %d image variable %s'), $row['id'], $row['var']), 
                _('Assessment'), $row['name'], "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']); 
        }
    }
} else if ($what === 'myqs') {
    // scan questionset control, qtext
    $query = 'SELECT iqs.control,iqs.qtext,iqs.id,iqs.extref FROM imas_questionset AS iqs 
    WHERE iqs.ownerid=?';
    $stm = $DBH->prepare($query);
    $stm->execute([$userid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        a11yscan($row['control'] . $row['qtext'], sprintf(_('Question ID %d'), $row['id']), 
            null, null, "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']);
        if (preg_match('/youtu[^!]*!!0/', $row['extref'])) {
            adderror(_('Uncaptioned video'), sprintf(_('Question ID %d'), $row['id']), 
                null, null, "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']); 
        }
    }

    // scan qimages alttext for blank
    $query = 'SELECT iqi.alttext,iqi.var,iqs.id FROM imas_questionset AS iqs 
    JOIN imas_qimages AS iqi ON iqi.qsetid=iqs.id
    WHERE iqs.ownerid=?';
    $stm = $DBH->prepare($query);
    $stm->execute([$userid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if (trim($row['alttext']) == '') {
            adderror(_('Blank alt text'), sprintf(_('Question ID %d image variable %s'), $row['id'], $row['var']), 
                null, null , "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']); 
        }
    }
}

if (count($vidids) > 0 && isset($CFG['YouTubeAPIKey'])) {
    $vidids = array_values(array_unique($vidids));
    $ph = Sanitize::generateQueryPlaceholders($vidids);
    $stm = $DBH->prepare("SELECT vidid,captioned,status FROM imas_captiondata WHERE vidid IN ($ph)");
    $stm->execute($vidids);
    $viddata = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $viddata[$row['vidid']] = [$row['captioned'], $row['status']];
    }
    $vidtoqueue = [];
    foreach ($vidids as $vidid) {
        if (!isset($viddata[$vidid]) || ($viddata[$vidid][0] == 0 && $viddata[$vidid][1] == 0)) {
            adderror(sprintf(_('Potentially uncaptioned video (ID %s; this video will be scanned in the next few days to check for captions)'), $vidid),
                $vidlocs[$vidid][0],$vidlocs[$vidid][1],$vidlocs[$vidid][2],$vidlocs[$vidid][3]);
            if (!isset($viddata[$vidid])) {
                $vidtoqueue[] = $vidid;
            }
        } else if ($viddata[$vidid][1] == 3) {
            adderror(sprintf(_('Missing/broken video (ID %s)'), $vidid),
                $vidlocs[$vidid][0],$vidlocs[$vidid][1],$vidlocs[$vidid][2],$vidlocs[$vidid][3]);
        } else if ($viddata[$vidid][0] == 0 && $viddata[$vidid][1] > 0) {
            adderror(sprintf(_('Uncaptioned video (ID %s)'), $vidid),
                $vidlocs[$vidid][0],$vidlocs[$vidid][1],$vidlocs[$vidid][2],$vidlocs[$vidid][3]);
        }
    }
    if (count($vidtoqueue) > 0) {
        $insarr = [];
        $now = time();
        foreach ($vidtoqueue as $vidid) {
            array_push($insarr, $vidid, $now);
        }
        $ph = Sanitize::generateQueryPlaceholdersGrouped($insarr,2);
        $stm = $DBH->prepare("INSERT IGNORE INTO imas_captiondata (vidid,lastchg) VALUES $ph");
        $stm->execute($insarr);
    }
}


$pagetitle = _('Accessibility Report');

$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">" . _('Course Reports') . "</a> ";


require_once "../header.php";
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

if ($what === 'myqs') {
    echo '<p>'._('This report scans questions you own for accessibility issues.').' ';
} else {
    echo '<p>'._('This report scans items in your course for accessibility issues.').' ';
}
if (isset($CFG['YouTubeAPIKey'])) {
    echo _('Currently it will only identify images that are missing alt text, and YouTube videos that do not have manual captions. It does not scan videos hosted elsewhere. Some YouTube videos might come back as "Potentially Uncaptioned", which just means we do not know yet; the video will be added to a queue and scanned in the next few days to check if has captions. The report will be updated once we have that information, so check back in a week or so.'). '</p>';
} else {
    echo _('Currently it will only identify images that are missing alt text, and YouTube videos added as helps to questions that do not have manual captions. YouTube links elsewhere are not scanned.'). '</p>';
}
echo '<p>'._('Note: Blank alt text can be valid, but should only be used to indicate a decorative image, one that does not add information to the page. For example, if the same information in the image is also included in adjacent text.').'</p>';

echo '<ul>';
foreach ($errors as $error) {
    echo '<li>';
    if (!empty($error[1])) {
        echo '<a href="' . Sanitize::encodeStringForDisplay($basesiteurl . '/' . $error[1]) . '" target="_blank">';
    }
    echo Sanitize::encodeStringForDisplay($error[0]);
    if (!empty($error[1])) {
        echo '</a>';
    }
    echo '</li>';
}
echo '</ul>';

echo '<p>'._('Videos marked as "Missing/broken" that seem to be available are likely unlisted videos, preventing lookup of caption data.').'</p>';

require_once '../footer.php';
