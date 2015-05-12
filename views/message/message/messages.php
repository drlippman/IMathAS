<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Messages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
    <?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
</div>
<div class="message-container">
<div><p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid='.$course->id.'&userid='.$course->ownerid); ?>" class="btn btn-primary ">Send New Message</a>
    | <a href="">Limit to Tagged</a> | <a href="">Sent Messages</a>
    | <a class="btn btn-primary ">Picture</a></p>
</div>
<div>
    <p><span class="col-md-1"><b>Filter By</b></span>
        <span class="col-md-3">
        <select name="seluid" class="dropdown form-control" id="seluid">
            <option value="0">All Courses</option>

        </select>
        </span> <span class="col-md-1"><b>To</b></span>
        <span class="col-md-3">
        <select name="seluid" class="dropdown form-control" id="seluid">
            <option value="0">Select a user..</option>

        </select>
        </span></p>
</div><br><br>
    <div>
        <p>check: <a href="">None</a>
            <a href="">All</a>
            With Selected:
            <a class="btn btn-primary ">Mark as Unread</a>
            <a class="btn btn-primary ">Mark as Read</a>
            <a class="btn btn-primary ">Delete</a>
    </div>

    <table class=gb id="myTable">
        <thead>
        <tr>
            <th></th>
            <th>Message</th>
            <th>Replied</th>
            <th></th>
            <th>Flag</th>
            <th>From</th>
            <th>Course</th>
            <th>Sent</th>
        </tr>
        </thead>
    </table>
</div>
    </select>
    </p>
</div>
