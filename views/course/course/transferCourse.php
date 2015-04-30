<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer Course Owner</title>
    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
    <!-- DataTables CSS -->
</head>
<body>
<div class=mainbody>
    <div id="headeradmin" class="pagetitle"><h2>Transfer Course Owner</h2></div>

<div>
    Transfer course ownership to <select name="seluid" class="dropdown" id="seluid">
        <option value="0" >Select a user..</option>
        <?php foreach ($users as $user) { ?>
            <option
                value="<?php echo $user['id'] ?>"><?php echo $user['FirstName'] . " " . $user['LastName']; ?></option>
        <?php } ?>
    </select>
</div>

    <div class="buttonAlignment">
        <a class="btn btn-primary transfer">Transfer</a>
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin','admin/index');?>" >Cancel</a>
    </div>
<input type="hidden" id="courseId" value="<?php echo $course['id'] ?>">
<input type="hidden" id="userId" value="<?php echo $course->ownerid ?>">
</div>


</body>
</html>
<script>
    $(".transfer").click(function(){

        var transferTo = $("#seluid option:selected").val();
        var courseId = $("#courseId").val();
        var ownerId = $("#userId").val();

        $.ajax({
            type: "POST",
            url: "update-owner",
            data:{
                newOwner:transferTo,
                cid:courseId,
                oldOwner:ownerId
            },
            success: function (response){
                console.log(response);
                var data = JSON.parse(response);
                if(data.status)
                {
                    alert('ok');
                    window.location = "admin/admin/index";
                }
            },
            error: function(xhRequest, ErrorText, thrownError) {
                console.log(ErrorText);
            }
        });

    });
</script>