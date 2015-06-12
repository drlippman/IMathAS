<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Messages';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css"
<!--      href="--><?php //echo AppUtility::getHomeURL() ?><!--js/DataTables-1.10.6/media/css/jquery.dataTables.css">-->
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<div>
    <?php if ($userRights->rights > 10) { ?>
        <?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
    <?php
    } else {

        echo $this->render('../../course/course/_toolbar', ['course' => $course]);
    } ?>
</div>
<div class="message-container">
    <div><p>
            <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>"
               class="btn btn-primary btn-sm">Send New Message</a>
            | <a id="limit-to-tag-link" href="#">Limit to Tagged</a> <a id="show-all-link" href="#">Show All</a> | <a
                href="<?php echo AppUtility::getURLFromHome('message', 'message/sent-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>">Sent
                Messages</a>
            |         <input type="button"  id='imgtab' class="btn btn-primary" value="Pictures" onclick="rotatepics()" ></p>
    </div>
    <div>
        <p><span class="select-text-margin pull-left"><b>Filter By Course :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-course form-control" id="course-id">
            <option value="0">All Courses</option>
        </select>
        </span> <span class="select-text-margin pull-left"><b>By Sender :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="user-id">
            <option value="0">Select a user</option>
        </select>
        </span></p>
    </div>
    <br><br>

    <div>
        <p>Check: <a id="check-all-box" class="check-all" href="#">All</a>/<a id="uncheck-all-box" class="uncheck-all"
                                                                              href="#">None</a>
            With Selected:
            <a class="btn btn-primary " id="mark-as-unread">Mark as Unread</a>
            <a class="btn btn-primary" id="mark-read">Mark as Read</a>
            <a class="btn btn-primary  btn-danger" id="mark-delete">Delete</a>
    </div>
    <div class="message-div"></div>
</div>