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
        <p><span class="col-md-2 select-text-margin" align="center"><b>Filter By Courses :</b></span>
        <span class="col-md-3">
            <select name="seluid" class="show-course form-control" id="seluid">
            <option value="0">All Courses</option>

        </select>

        </span> <span class="col-md-2 select-text-margin" align="center"><b>By Recipient :</b></span>

        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="seluid">
            <option value="0">Select a user</option>
            </select>

        </span></p>
    </div><br><br>
    <div>
        <p>check: <a id="uncheck-all-box" class="uncheck-all" href="#">None</a> /
            <a id="check-all-box" class="check-all" href="#">All</a>
            With Selected:
            <a class="btn btn-primary btn-sm"id="mark-sent-delete">Remove From Sent Message List</a>
            <a class="btn btn-primary btn-sm">Unsend</a>
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
<script type="text/javascript">
    $(document).ready(function () {
        var cid = $(".send-course-id").val();
        var userId = $(".send-user-id").val();
        var inputData = {cid: cid, userId: userId};

        jQuerySubmit('display-sent-message-ajax',inputData, 'showMessageSuccess');
        selectCheckBox();
        jQuerySubmit('get-course-ajax',  inputData, 'getCourseSuccess');
        markSentDelete();
    });

    function showMessageSuccess(response)
    {console.log(response);
        var filterArrayForUser = [];
        $.each(JSON.parse(response), function(index, messageData){
            $.each(messageData, function(index, msgData){
                filterArrayForUser.push(msgData.msgTo);      //msgFrom ->msgTo
            });

        });
        var uniqueUserForFilter = filterArrayForUser.filter(function(itm,i,a){
            return i==a.indexOf(itm);
        });

        var htmlCourse = '';
        for(i = 0; i<uniqueUserForFilter.length; i++){
            htmlCourse += "<option value = messageData.msgTo>"+uniqueUserForFilter[i]+"</option>"  //msgFrom->msgTo

        }
        $(".show-users").append(htmlCourse);

        var result = JSON.parse(response);
        if(result.status == 0)
        {
            var messageData = result.messageData;
            showMessage(messageData);
        }
    }



    function showMessage(messageData)
    {console.log(messageData);
        var html = "";
        var htmlCourse ="";
        $.each(messageData, function(index, messageData){
            html += "<tr> <td><input type='checkbox' name='msg-check' value='"+messageData.msgId+"' class='message-checkbox-"+messageData.mmsgId+"' ></td>";
            html += "<td><a href='#'>"+messageData.title+"</a></td>";
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
    function    getCourseSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            var courseData = result.courseData;
            courseDisplay(courseData);
        }

        function courseDisplay(courseData)
        {
            var html = "";
            $.each(courseData,function(index, courseData){
                html += "<option value = courseData.courseId>"+courseData.courseName+"</option>"
            });
            $(".show-course").append(html);
        }
    }
   function markSentDelete()
    {
        $("#mark-sent-delete").click(function(){

           var markArray = [];
            $('.message-table-body input[name="msg-check"]:checked').each(function() {
              markArray.push($(this).val());
                $(this).closest('tr').remove();
               $(this).prop('checked',false);
            });
            var readMsg={checkedMsgs: markArray};
           jQuerySubmit('mark-sent-remove-ajax',readMsg,'markDeleteSuccess');

        });

    }
    function markDeleteSuccess(){
            console.log('Success');
    }

</script>