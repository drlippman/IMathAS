<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Manage Teacher';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<h3>Current Teachers</h3>

<input type="hidden" class="course-id" value="<?php echo $cid ?>">

<div>
    <div class="lg-col-2 pull-left select-text-margin">
        <strong>With Selected:</strong>&nbsp;&nbsp;
    </div>
    <a class='btn btn-primary addRemoveTeacherButton addButton addTeacherButton-"+nonTeacher.id+" '
       onclick='removeAllAsTeacher()'>Remove as Teacher </a>
    <table class="addRemoveTable teachers" id="teach">

    </table>
</div>

<h3>Potential Teachers</h3>

<div>
    <div class="lg-col-2 pull-left select-text-margin">
        <strong>With Selected:</strong> &nbsp;&nbsp;
        <table class="addRemoveTable non-teachers" id="nonTeach">
            <a class='btn btn-primary addRemoveTeacherButton removeButton removeTeacherButton-"+teacher.id+" '
               onclick='addAllAsTeacher()'>Add as Teacher </a>&nbsp;&nbsp;
        </table>
    </div>
</div>