<?php
// IMathAS: Generate one-time passwords for an assessment
// (c) 2025 David Lippman

/*** master php includes *******/
require_once "../init.php";

/*** permissions checks */
if (!(isset($teacherid))) { // loaded by a NON-teacher
	echo "You need to log in as a teacher to access this page";
    exit;
} elseif (!(isset($_GET['aid']))) {
	echo "You need to access this page from the assessments page menu";
    exit;
}

$aid = Sanitize::onlyInt($_GET['aid']);
$now = time();

// check assessment is in course
$stm = $DBH->prepare("SELECT name,courseid FROM imas_assessments WHERE id=?");
$stm->execute([$aid]);
$adata = $stm->fetch(PDO::FETCH_ASSOC);
if ($adata['courseid'] != $cid) {
    echo "Invalid ID";
    exit;
}

// get existing codes
$stm = $DBH->prepare("SELECT code FROM imas_onetime_pw WHERE assessmentid=? ORDER BY createdon");
$stm->execute([$aid]);
$allcodes = $stm->fetchALL(PDO::FETCH_COLUMN, 0);

// process POST for generating codes
if (isset($_POST['genn'])) {
    // generate more codes
    $n = intval($_POST['genn']);
    // don't allow more that 500 codes total to be generated for an assessment
    if ($n + count($allcodes) > 500) {
        $n = 500 - count($allcodes);
    }
    if ($n > 0) {
        $inserts = [];
        $chars = '23456789abcdfghjkmnpqrstvwxyzABDEFGHJKLMNPQRSTVWYZ';
        $max = strlen($chars)-1;
        for ($i=0; $i < $n; $i++) {
            $code = '';
            for ($j=0;$j<6;$j++) {
                $code .= $chars[rand(0,$max)];
            }
            array_push($inserts, $aid, $code, $now);
        }
        $ph = Sanitize::generateQueryPlaceholdersGrouped($inserts, 3);
        $stm = $DBH->prepare("INSERT INTO imas_onetime_pw (assessmentid,code,createdon) VALUES $ph");
        $stm->execute($inserts);
        header(sprintf('Location: %s/course/assessonetime.php?cid=%s&aid=%d&newn=%d&r=' . Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
            $cid, $aid, $n));
        exit;
    } else if (count($allcodes)>=500) {
        echo 'You already have 500 codes. No more can be created until some are used.';
        exit;
    }
    echo 'Invalid n';
    exit;
}

$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
$curBreadcrumb .= "<a href=\"addassessment2.php?cid=$cid&id=$aid\">"._('Modify Assessment').'</a> ';
$curBreadcrumb .= '&gt; ' . _('One-time Passwords');

/******* begin html output ********/
$pagetitle = _('One-time Passwords');
$placeinhead = '<script>$(setupToggler("body"));</script>';
require_once "../header.php";
?>
<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
<div class="pagetitle">
    <h1><?php echo $pagetitle; ?></h1>
    <h2><?php echo Sanitize::encodeStringForDisplay($adata['name'])?></h2>
</div>
<?php
// show message if just added
if (isset($_GET['newn'])) {
    echo '<p>' . sprintf(_('%d new codes were created'), Sanitize::onlyInt($_GET['newn'])) . '</p>';
}
// show old codes if none exist
if (count($allcodes) > 0) {
    echo '<p>'.sprintf(_('There are %d unused one-time codes for this assessment'), count($allcodes)).'</p>';
    echo '<div data-toggler="' . _('View Codes') . '">';
    echo '<ul>';
    foreach ($allcodes as $code) {
        echo '<li>' . Sanitize::encodeStringForDisplay($code) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
} else {
    echo '<p>' . _('There are no unused one-time codes for this assessment');
}

echo '<form method=post>';
echo '<p>'._('Generate new codes').': ';
echo '<label><input type=text name=genn size=3 /> '._('new codes').'</label>';
echo '<button type=submit>'._('Generate').'</button>';
echo '</form>';

require '../footer.php';
