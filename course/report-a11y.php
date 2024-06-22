<?php

require_once "../init.php";

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
}

$errors = [];
function a11yscan($content, $field, $type, $itemname, $link='') {
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
}
function adderror($descr, $loc, $itemtype, $itemname, $link) {
    global $errors;
    $errors[] = [sprintf('%s in %s of %s %s', $descr, $loc, $itemtype, $itemname), $link];
}

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
$query = 'SELECT iqs.control,iqs.qtext,ia.name,iqs.id FROM imas_questionset AS iqs 
    JOIN imas_questions AS iq ON iqs.id=iq.questionsetid
    JOIN imas_assessments AS ia ON ia.id=iq.assessmentid
    WHERE ia.courseid=?';
$stm = $DBH->prepare($query);
$stm->execute([$cid]);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    a11yscan($row['control'] . $row['qtext'], sprintf(_('Question ID %d'), $row['id']), 
        _('Assessment'), $row['name'], "course/testquestion2.php?cid=$cid&qsetid=" . $row['id']);
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


$pagetitle = _('Accessibility Report');

$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">" . _('Course Reports') . "</a> ";


require_once "../header.php";
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

echo '<p>'._('This report scans items in your course for accessibility issues.').' ';
echo _('Currently it will only identify images that are missing alt text.'). '</p>';
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

require_once '../footer.php';
