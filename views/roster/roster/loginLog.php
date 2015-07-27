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
    <h3><strong>View Login Log</strong></h3>
    <pre><a href="<?php echo AppUtility::getURLFromHome('roster','roster/activity-log?cid='.$course->id.'&uid='.$userId) ;?>">View Activity Log</a></pre>
    <h4><strong>Login Log for <?php echo $userFullName ?></strong></h4>
    <?php
        foreach($lastlogin as $login) { ?>
           <p><?php echo $login['logDateTime'] ; ?></p>
        <?php } ?>

</div>