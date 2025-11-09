<?php

require_once '../init.php';
require_once '../includes/videodata.php';

if ($myrights < 20) {
    echo 'No rights';
    exit;
}

$pagetitle = _('Video Rescan');
$curBreadcrumb = "$breadcrumbbase $pagetitle";

require '../header.php';

echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
echo '<h1>'.$pagetitle.'</h1>';

if (!empty($_POST['video'])) {
    echo '<p class="noticetext">';
    $vidid = getvideoid(($_POST['video']));
    if ($vidid == '' || !preg_match('/^[a-zA-Z0-9_-]{11}$/', $vidid)) {
        echo _("No valid video ID found");
    } else {
        $stm = $DBH->prepare("SELECT status,captioned FROM imas_captiondata WHERE vidid=?");
        $stm->execute([$vidid]);
        $r = $stm->fetch(PDO::FETCH_ASSOC);
        if ($r === false) {
            echo _('Video has not been used anywhere yet. Use the video somewhere, then run the accessibility report to initiate a scan');
        } else if ($r['captioned'] == 1) {
            echo _('Video is already recognized as captioned');
        } else if ($r['status'] == 0) {
            echo _('Video is already queued for a scan');
        } else {
            $stm = $DBH->prepare("UPDATE imas_captiondata SET status=0,lastchg=? WHERE vidid=?");
            $stm->execute([time(), $vidid]);
            echo sprintf(_('Video %s added to queue for scanning in the next few days'), Sanitize::encodeStringForDisplay($vidid));
            if ($r['status'] == 3) {
                echo '. '._('Note that last time this video was scanned, caption data was inaccessible, which can be caused if the video is unlisted on YouTube. If this is your video, make sure it is set to public to allow it to be scanned.');
            }
        }
    }
    echo '</p>';
}
echo '<form method=post>';
echo '<p>';
echo _('If there is a video used in an assessment summary, assessment intro, or other text item that you have recently added captions to, or have switched from unlisted to public, you can use this form to request a re-scan of the video to update the caption info used for the accessibility report.').' ';
echo _('Note that for videos added as help to questions, you should remove and re-add the video to trigger a re-scan and update the caption flag on the question.');
echo '</p>';
echo '<p><label>'._('Video to rescan:').' <input name=video size=30 /></label></p>';
echo '<p><button type=submit>'._('Add Video to Queue').'</button></p>';
echo '</form>';
require '../footer.php';