<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Sent Messages';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <!-- DataTables CSS -->

    <link rel="stylesheet" type="text/css"
          href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>


</head>
<body>
<div>
    <?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>
    <input type="hidden" class="send-course-id" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-user-id" value="<?php echo $course->ownerid ?>">
</div>
<div class="message-container">
    <div><p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>">Received Messages</a></p>
    </div>
    <div>
        <p><span class="col-md-2" align="center"><b>Filter By Courses :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="dropdown form-control" id="seluid">
            <option value="0">All Courses</option>

        </select>

        </span> <span class="col-md-2" align="center"><b>By Recipient :</b></span>

        <span class="col-md-3">
        <select name="seluid" class="dropdown form-control" id="seluid">
            <option value="0">Select a user..</option>

            <!-- script is missing -->

        </select>

        </span></p>
    </div><br><br>
    <div>
        <p>check: <a id="uncheck-all-box" class="uncheck-all" href="#">None</a> /
            <a id="check-all-box" class="check-all" href="#">All</a>
            With Selected:
            <a class="btn btn-primary ">Remove From Sent Message List</a>
            <a class="btn btn-primary ">Unsend</a>
        </p>

    </div>

    <table id="message-table display-message-table" class="message-table display-message-table">
        <thead>
        <tr>
            <th></th>
            <th>Message</th>
            <th>To</th>
            <th>Read</th>
            <th>Sent</th>
        </tr>
        </thead>
        <tbody class="message-table-body">
        </tbody>
    </table>
</div>
</body>
</html>

<!-- Script for table is remaining-->
<script type="text/javascript">
    $(document).ready(function () {
        var cid = $(".send-course-id").val();
        var userId = $(".send-user-id").val();
        var inputData = {cid: cid, userId: userId};
        jQuerySubmit('display-sent-message-ajax',inputData, 'showMessageSuccess');
        selectCheckBox();
    });

    function showMessageSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            var messageData = result.messageData;
            showMessage(messageData);
        }
    }

    function showMessage(messageData)
    {
        var html = "";
        $.each(messageData, function(index, messageData){
            html += "<tr> <td><input type='checkbox' name='msg-check' value='"+messageData.id+"' class='message-checkbox-"+messageData.id+"' ></td>";
            html += "<td>"+messageData.title+"</td>";
            html += "<td>"+messageData.msgTo+"</td>";
            html += "<td>"+messageData.isRead+"</td>";
            html += "<td>"+messageData.msgDate+"</td>";
        });
        $(".message-table-body").append(html);
        $('.display-message-table').DataTable();
    }

    function selectCheckBox(){
        $('.check-all').click(function(){
            $('.message-table-body input:checkbox').each(function(){
                $(this).prop('checked',true);
            })
        });

        $('.uncheck-all').click(function(){
            $('.message-table-body input:checkbox').each(function(){
                $(this).prop('checked',false);
            })
        });
    }

</script>