<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Manage Teacher';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
<div class="col-md-12"><h3>Current Teachers</h3></div>
<input type="hidden" class="course-id" value="<?php echo $cid ?>">

<div>
    <div class="col-md-10 pull-left select-text-margin">
        <strong>With Selected:</strong>&nbsp;&nbsp;

    <a class='btn btn-primary addRemoveTeacherButton addButton addTeacherButton-"+nonTeacher.id+" '
       onclick='removeAllAsTeacher()'>Remove as Teacher </a>
    <table class="addRemoveTable teachers" id="teach">
    </table>
    </div>
</div>

<div class="col-md-12"><h3>Potential Teachers</h3></div>

<div>
    <div class="col-md-10 pull-left select-text-margin">
        <strong>With Selected:</strong> &nbsp;&nbsp;
        <table class="addRemoveTable non-teachers" id="nonTeach">
            <a class='btn btn-primary addRemoveTeacherButton removeButton removeTeacherButton-"+teacher.id+" '
               onclick='addAllAsTeacher()'>Add as Teacher </a>&nbsp;&nbsp;
        </table>
    </div>
</div>
</div>