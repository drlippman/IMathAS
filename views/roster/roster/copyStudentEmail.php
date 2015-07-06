<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'New Message';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
if($gradebook == AppConstant::NUMERIC_ONE){
    $this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
}else{
    $this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
}
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<div><p><strong>Copy Student Emails</strong></p>
    <textarea style="width: 50%" cols="40" rows="20"><?php echo trim($studentData) ?></textarea>
</div>