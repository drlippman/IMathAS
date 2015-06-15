<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Sent Messages';

if ($userRights->rights > 10){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<div>
    <input type="hidden" class="send-course-id" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-user-id" value="<?php echo $course->ownerid ?>">
    <?php if ($userRights->rights > 10) { ?>
        <?php echo $this->render('../../instructor/instructor/_toolbarTeacher',['course' => $course]); ?>

    <?php } else {?>

        <?php echo $this->render('../../course/course/_toolbar', ['course' => $course]);?>

    <?php } ?>

</div>
<div class="message-container">
    <div><p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>">Received Messages</a></p>
    </div>
    <div>
        <p><span class="select-text-margin pull-left"><b>Filter By Courses :</b></span>
        <span class="col-md-3">
            <select name="seluid" class="show-course form-control" id="course-sent-id">
            <option value="0">All Courses</option>
            </select>

        </span> <span class="select-text-margin pull-left"><b>By Recipient :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="user-sent-id">
            <option value="0">Select a user</option>
            </select>
        </span></p>
    </div><br><br>
    <div>
        <p>Check: <a id="check-all-box" class="check-all" href="#">All</a>/<a id="uncheck-all-box" class="uncheck-all" href="#">None</a>
            With Selected:
            <a class="btn btn-primary"id="mark-sent-delete">Remove From Sent Message List</a>
            <a class="btn btn-primary" id="mark-unsend">Unsend</a>
        </p>
    </div>
    <div class="message-div"></div>
    </div>