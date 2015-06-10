<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'Login Log';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
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

        </tbody>
    </table>
</div>