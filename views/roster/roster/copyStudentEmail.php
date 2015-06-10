<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'New Message';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
<div><p><strong>Copy Student Emails</strong></p>
    <textarea style="width: 50%" cols="40" rows="20"><?php echo trim($studentData) ?></textarea>
</div>