<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'Login Log';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<div>
    <h3><strong>View Activity Log</strong></h3>
    <pre><a href="<?php echo AppUtility::getURLFromHome('roster','roster/login-log?cid='.$course->id.'&uid='.$userId) ?>">View Login Log</a></pre>
    <h4><strong>Activity Log for <?php echo $userFullName ?></strong></h4>
    <table id="user-table displayCourse" class="display user-table">
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
        $recordsArray = explode('::',$record['info']);
        if (count($recordsArray)==2) {
            $thelink = '<a href="'.$recordsArray[0].'" target="_blank">'.$recordsArray[1].'</a>';
            $href = $recordsArray[0];
        } else {
            $thelink = $record['info'];
            $href = $record['info'];
        }
    }
    echo '<tr>';
    echo '<td>'.AppUtility::tzdate("l, F j, Y, g:i a",$record['viewtime']).'</td>';
    echo '<td>';
    switch ($record['type']) {
        case 'inlinetext':
            echo 'In inline text item '.$innames[$record['typeid']].', clicked link to '.$thelink;
            break;
        case 'linkedsum':
            echo 'From linked item '.$linames[$record['typeid']].' summary, clicked link to '.$thelink;
            break;
        case 'linkedlink':
            if ($record['info']==$record['typeid'] || (strpos($href,$record['typeid'])!==false && strpos($href,'id='.$record['typeid'])!==false)) {
                echo 'Opened linked text item '.$linames['linlkTextName'];
            } else {
                echo 'Clicked linked item <a target="_blank" href="'.$href.'">'.$linames['linlkTextName'].'</a>';
            }
            break;
        case 'linkedintext':
            echo 'In linked text '.$linames[$record['typeid']].', clicked link to '.$thelink;
            break;
        case 'linkedviacal':
            if ($record['info']==$record['typeid'] || (strpos($href,'showlinkedtext')!==false && strpos($href,'id='.$record['typeid'])!==false)) {
                echo 'Via calendar, opened linked text item '.$linames[$record['typeid']];
            } else {
                echo 'Via calendar, clicked linked item <a target="_blank" href="'.$href.'">'.$linames[$record['typeid']].'</a>';
            }
            break;
        case 'extref':
            $p = explode(': ',$record['info']);
            echo 'In assessment '.$exnames[$record['typeid']].', clicked help for <a target="_blank" href="'.$p[1].'">'.$p[0].'</a>';
            break;
        case 'assessintro':
            echo 'In assessment '.$asnames[$record['typeid']].' intro, clicked link to '.$thelink;
            break;
        case 'assesssum':
            echo 'In assessment '.$asnames[$record['typeid']].' summary, clicked link to '.$thelink;
            break;
        case 'assess':
            echo 'Opened assessment '.$asnames[$record['typeid']];

            break;
        case 'assesslti':
            echo 'Opened assessment '.$asnames[$record['typeid']].' via LTI';
            break;
        case 'assessviacal':
            echo 'Via calendar, opened assessment '.$asnames[$record['typeid']];
            break;
        case 'wiki':
            echo 'Opened wiki '.$winames[$record['typeid']];
            break;
        case 'wikiintext':
            echo 'In wiki '.$winames[$record['typeid']].', clicked link to '.$thelink;
            break;
        case 'forumpost':
            $fp = explode(';',$record['info']);
            echo 'New post <a target="_blank" href="../forums/posts.php?cid='.$courseId.'&forum='.$fp[0].'&thread='.$record['typeid'].'">'.$fpnames[$record['typeid']].'</a> in forum '.$forumnames[$fp[0]];
            break;
        case 'forumreply':
            $fp = explode(';',$record['info']);
            echo 'New reply <a target="_blank" href="../forums/posts.php?cid='.$courseId.'&forum='.$fp[0].'&thread='.$fp[1].'">'.$fpnames[$record['typeid']].'</a> in forum '.$forumnames[$fp[0]];
            break;
        case 'forummod':
            $fp = explode(';',$record['info']);
            echo 'Modified post/reply <a target="_blank" href="../forums/posts.php?cid='.$courseId.'&forum='.$fp[0].'&thread='.$fp[1].'">'.$fpnames['forumPostName'].'</a> in forum '.$forumnames[$fp[0]];
            break;
    }
}
?>
        </tbody>
    </table>
</div>