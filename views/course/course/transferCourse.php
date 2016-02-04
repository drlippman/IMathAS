<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Transfer Course';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
    <title>Transfer Course Owner</title>
    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
    <!-- DataTables CSS -->

<div class=mainbody>
    <div id="headeradmin" class="pagetitle"><h2>Transfer Course Owner</h2></div>
    <div class="form-group">
        <label class="col-md-3 label-text-margin pull-left">Transfer course ownership to</label>
        <div class="col-md-3 pull-left">
            <select name="seluid" class="dropdown form-control" id="seluid">
                <option value="0">Select a user..</option>
                <?php foreach ($users as $user) { ?>
                    <option
                        value="<?php echo $user['id'] ?>"><?php echo $user['FirstName'] . " " . $user['LastName']; ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="clear-both"></div>
    <div class="col-md-offset-3 buttonAlignment">
        <a class="btn btn-primary transfer">Transfer</a>
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/index'); ?>">Back</a>
    </div>
    <input type="hidden" id="courseId" value="<?php echo $course['id'] ?>">
    <input type="hidden" id="userId" value="<?php echo $course->ownerid ?>">
</div>

<script>
$( document ).ready(function() {
    $(".transfer").click(function () {
        var transferTo = $("#seluid option:selected").val();
        var courseId = $("#courseId").val();
        var ownerId = $("#userId").val();
        var transferData = {newOwner: transferTo,cid: courseId,oldOwner: ownerId};
        jQuerySubmit('update-owner', transferData, 'updateSuccess');

    });
});

function updateSuccess(response) {
    var data = response;
    if (data.status==0) {
        $("#flash-message").html('<div class="alert alert-success">Ownership transferred successfully.</div>');
    }
}
</script>