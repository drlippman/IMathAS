<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = AppUtility::t('Activity Log', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if(isset($from) && $from=='gb'){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false),AppUtility::t('Student Detail', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,
                AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId=0'),AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$userId)]]);
    }else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getURLFromHome('roster','roster/student-roster?cid='.$course->id)]]);

    } ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <div class="activity-log-header">
        <a class="padding-left-thirty" href="<?php echo AppUtility::getURLFromHome('roster', 'roster/login-log?cid=' . $course->id . '&uid=' . $userId) ?>">View Login Log</a>
    </div>
    <div class="roster-activity-log">
        <div class="col-md-12">
            <h4 class="padding-top-twenty padding-bottom-ten">
                <strong>Activity Log for <?php echo $userFullName ?></strong>
            </h4>
        </div>
        <table id="user-table displayCourse" class="display user-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody class="user-table-body">
            <?php
            foreach ($actions as $record) {
                if (isset($record['info'])) {
                    $recordsArray = explode('::', $record['info']);
                    if (count($recordsArray) == 2) {
                        $hyperlink = '<a href="' . $recordsArray[0] . '" target="_blank">' . $recordsArray[1] . '</a>';
                        $href = $recordsArray[0];
                    } else {
                        $hyperlink = $record['info'];
                        $href = $record['info'];
                    }
                }
                echo '<tr>';
                echo '<td>' . AppUtility::tzdate("l, F j, Y, g:i a", $record['viewtime']) . '</td>';
                echo '<td>';
                switch ($record['type']) {
                    case 'inlinetext':
                        echo 'In inline text item ' . $inlineTextName[$record['typeid']] . ', clicked link to ' . $hyperlink;
                        break;
                    case 'linkedsum':
                        echo 'From linked item ' . $linkName[$record['typeid']] . ' summary, clicked link to ' . $hyperlink;
                        break;
                    case 'linkedlink':
                        if ($record['info'] == $record['typeid'] || (strpos($href, 'showlinkedtext') !== false && strpos($href, 'id=' . $record['typeid']) !== false)) {
                            echo 'Opened linked text item ' . $linkName[$record['typeid']];
                        } else {
                            echo 'Clicked linked item <a target="_blank" href="' . $href . '">' . $linkName[$record['typeid']] . '</a>';
                        }
                        break;
                    case 'linkedintext':
                        echo 'In linked text ' . $linkName[$record['typeid']] . ', clicked link to ' . $hyperlink;
                        break;
                    case 'linkedviacal':
                        if ($record['info'] == $record['typeid'] || (strpos($href, 'showlinkedtext') !== false && strpos($href, 'id=' . $record['typeid']) !== false)) {
                            echo 'Via calendar, opened linked text item ' . $linkName[$record['typeid']];
                        } else {
                            echo 'Via calendar, clicked linked item <a target="_blank" href="' . $href . '">' . $linkName[$record['typeid']] . '</a>';
                        }
                        break;
                    case 'extref':
                        $post = explode(': ', $record['info']);
                        echo 'In assessment ' . $exnames[$record['typeid']] . ', clicked help for <a target="_blank" href="' . $post[1] . '">' . $post[0] . '</a>';
                        break;
                    case 'assessintro':
                        echo 'In assessment ' . $assessmentName[$record['typeid']] . ' intro, clicked link to ' . $hyperlink;
                        break;
                    case 'assesssum':
                        echo 'In assessment ' . $assessmentName[$record['typeid']] . ' summary, clicked link to ' . $hyperlink;
                        break;
                    case 'assess':
                        echo 'Opened assessment ' . $assessmentName[$record['typeid']];

                        break;
                    case 'assesslti':
                        echo 'Opened assessment ' . $assessmentName[$record['typeid']] . ' via LTI';
                        break;
                    case 'assessviacal':
                        echo 'Via calendar, opened assessment ' . $assessmentName[$record['typeid']];
                        break;
                    case 'wiki':
                        echo 'Opened wiki ' . $wikiName[$record['typeid']];
                        break;
                    case 'wikiintext':
                        echo 'In wiki ' . $wikiName[$record['typeid']] . ', clicked link to ' . $hyperlink;
                        break;
                    case 'forumpost':
                        $forumPost = explode(';', $record['info']);
                        echo 'New post <a target="_blank" href="' . AppUtility::getURLFromHome("forum", "forum/post?courseid=" . $course->id . "&forumid=" . $forumPost[0] . "&threadid=" . $record['typeid']) . '">' . $forumPostName[$record['typeid']] . '</a> in forum ' . $forumName[$forumPost[0]];
                        break;
                    case 'forumreply':
                        $forumPost = explode(';', $record['info']);
                        echo 'New reply <a target="_blank" href="' . AppUtility::getURLFromHome("forum", "forum/post?courseid=" . $course->id . "&forumid=" . $forumPost[0] . "&threadid=" . $forumPost[1]) . '">' . $forumPostName[$record['typeid']] . '</a> in forum ' . $forumName[$forumPost[0]];
                        break;
                    case 'forummod':
                        $forumPost = explode(';', $record['info']);
                        echo 'Modified post/reply <a target="_blank" href="' . AppUtility::getURLFromHome("forum", "forum/post?courseid=" . $course->id . "&forumid=" . $forumPost[0] . "&threadid=" . $forumPost[1]) . '">' . $forumPostName['forumPostName'] . '</a> in forum ' . $forumName[$forumPost[0]];
                        break;
                }
            }
            ?>
            </tbody>
        </table>
    </div>

</div>
