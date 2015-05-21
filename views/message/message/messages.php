<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Messages';
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
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
</div>
<div class="message-container">
<div><p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid='.$course->id.'&userid='.$course->ownerid); ?>" class="btn btn-primary btn-sm">Send New Message</a>
    | <a href="">Limit to Tagged</a> | <a href="<?php echo AppUtility::getURLFromHome('message', 'message/sent-message?cid='.$course->id.'&userid='.$course->ownerid); ?>">Sent Messages</a>
    | <a class="btn btn-primary btn-sm">Picture</a></p>
</div>
<div>
    <p><span class="col-md-2 select-text-margin" align="center"><b>Filter By Course :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-course form-control" id="course-id">
            <option value="0">All Courses</option>

        </select>

        </span> <span class="col-md-2 select-text-margin" align="center"><b>By Sender :</b></span>

        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="user-id">
            <option value="0">Select a user</option>
        </select>
        </span></p>
</div><br><br>
    <div>

        <p>check: <a id="uncheck-all-box" class="uncheck-all" href="#">None</a> /
            <a id="check-all-box" class="check-all" href="#">All</a>
            With Selected:
            <a class="btn btn-primary " id="mark-as-unread">Mark as Unread</a>
            <a class="btn btn-primary" id="mark-read">Mark as Read</a>
            <a class="btn btn-primary  btn-danger" id="mark-delete">Delete</a>
    </div>
    <table id="message-table display-message-table" class="message-table display-message-table">
        <thead>
        <tr>
            <th></th>
            <th>Message</th>
            <th>Replied</th>
            <th>Flag</th>
            <th>From</th>
            <th>Course</th>
            <th>Sent</th>
        </tr
        </thead>
        <tbody class="message-table-body">
        </tbody>
    </table>
</div>
</body>
</html>
<script type="text/javascript">
    $(document).ready(function () {
        var cid = $(".send-msg").val();
        var userId = $(".send-userId").val();
        var allMessage = {cid: cid, userId: userId};
        jQuerySubmit('display-message-ajax',allMessage, 'showMessageSuccess');
        jQuerySubmit('get-course-ajax',  allMessage, 'getCourseSuccess');
        jQuerySubmit('get-user-ajax',  allMessage, 'getUserSuccess');
        selectCheckBox();
        markAsRead();
        markAsUnread();
        markAsDelete();
    });
    var messageData;
    function showMessageSuccess(response)
    {
        var filterArrayForUser = [];
        $.each(JSON.parse(response), function(index, messageData){
            $.each(messageData, function(index, msgData){
                filterArrayForUser.push(msgData.msgFrom);
            });

        });
        var uniqueUserForFilter = filterArrayForUser.filter(function(itm,i,a){
            return i==a.indexOf(itm);
        });

        var htmlCourse = '';
        for(i = 0; i<uniqueUserForFilter.length; i++){
            htmlCourse += "<option value = "+uniqueUserForFilter+">"+uniqueUserForFilter[i]+"</option>"

        }
      //  $(".show-user").append(htmlCourse);

        var result = JSON.parse(response);
        if(result.status == 0)
        {
             messageData = result.messageData;
            showMessage(messageData);
        }
    }

    function showMessage(messageData)
    {
        var html = " ";
        var htmlCourse ="";
        $.each(messageData, function(index, messageData)
        {
            if(messageData.isread == 1 || messageData.isread == 5 ||messageData.isread == 9 ||messageData.isread == 13)
             {
                html += "<tr class='read-message message-row message-row-'"+messageData.id+"> <td><input type='checkbox' id='Checkbox' name='msg-check' value='"+messageData.id+"' class='message-checkbox-"+messageData.id+"' ></td>";
            }
            else
            {
                html += "<tr class='unread-message message-row message-row-"+messageData.id+"'> <td><input type='checkbox' id='Checkbox' name='msg-check' value='"+messageData.id+"' class='message-checkbox-"+messageData.id+"' ></td>";
            }
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('message', 'message/view-message?id=')?>"+messageData.id+"'> "+messageData.title+"</a></td>";
            if(messageData.replied == 1)
            {
                html += "<th>Yes</th>";
            }
            else{
                html += "<th>No</th>";
            }
            var rowid = messageData.id;

            if(messageData.isread < 7)
            {
                html += "<td><img src='<?php echo AppUtility::getHomeURL() ?>img/flagempty.gif' onclick='changeImage(this,"+rowid+")'/></td>";
            }
            else if(messageData.isread == 1 || messageData.isread == 0){
                html += "<td><img src='<?php echo AppUtility::getHomeURL() ?>img/flagempty.gif' onclick='changeImage(this,"+rowid+")'/></td>";
            }
            else{
                html += "<td><img src='<?php echo AppUtility::getHomeURL() ?>img/flagfilled.gif' onclick='changeImage(this,"+rowid+")'/></td>";
            }
            html += "<td>"+messageData.FirstName.substr(0,1).toUpperCase()+ messageData.FirstName.substr(1)+" "+messageData.LastName.substr(0,1).toUpperCase()+ messageData.LastName.substr(1)+"</td>";
            html += "<td>"+messageData.name.substr(0,1).toUpperCase()+ messageData.name.substr(1)+"</td>";

            html += "<td>"+messageData.senddate+"</td>";

        });

        $(".message-table-body tr").remove();
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
            filterByCourse();
        }
    }

    function courseDisplay(courseData)
    {
        var html = "";
        $.each(courseData,function(index, courseData){
            html += "<option value = "+courseData.courseId+">"+courseData.courseName+"</option>"
        });
        $(".show-course").append(html);
    }
    function markAsUnread()
    {
        $('#mark-as-unread').click(function(){
            var markArray = [];
            $('.message-table-body input[name="msg-check"]:checked').each(function(){
                $(this).closest('tr').css('font-weight', 'bold');
                markArray.push($(this).val());
                $(this).prop('checked',false);

            });

            var readMsg = {checkedMsg: markArray};
            jQuerySubmit('mark-as-unread-ajax', readMsg, 'markAsUnreadSuccess');
        });
    }

    function markAsUnreadSuccess(response)
    {

    }

    function markAsRead(){
        $("#mark-read").click(function(){
            var markArray = [];
            $('.message-table-body input[name="msg-check"]:checked').each(function() {
                markArray.push($(this).val());
                $(this).closest('tr').css('font-weight', 'normal');
                $(this).prop('checked',false);



            });
            var readMsg={checkedMsg: markArray};
            jQuerySubmit('mark-as-read-ajax',readMsg,'markAsReadSuccess');

        });


    }
    function markAsReadSuccess(response)
    {

    }


    function filterByCourse()
    {
        $('#course-id').on('change', function() {
            var filteredArray = [];
            var selectedCourseId = this.value;
            if(selectedCourseId == 0 ){
                    showMessage(messageData);
            }else{
            $.each(messageData, function(index, messageData){
                if(selectedCourseId == messageData.courseid ){
                    filteredArray.push(messageData);
                }
                showMessage(filteredArray);
            });
            }
        });
    }

    function getUserSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            var userData = result.userData;
            userDisplay(userData);
            filterByUser();
        }
    }

    function userDisplay(userData)
    {
        var html = "";
        $.each(userData,function(index, userData){
            html += "<option value = "+userData.id+">"+userData.FirstName+" "+userData.LastName+"</option>"
        });
        $(".show-users").append(html);
    }

    function markAsDelete(){
        $("#mark-delete").click(function(){

            var markArray = [];
            $('.message-table-body input[name="msg-check"]:checked').each(function() {
                markArray.push($(this).val());
                $(this).closest('tr').remove();
                $(this).prop('checked',false);
            });
            var readMsg={checkedMsg: markArray};
            jQuerySubmit('mark-as-delete-ajax',readMsg,'markAsDeleteSuccess');

        });

    }
    function markAsDeleteSuccess(){

    }

    function filterByUser()
    {
        $('#user-id').on('change', function() {
            var filteredArray = [];
            var selectedUserId = this.value;
            if(selectedUserId == 0){
                showMessage(messageData);
            }else {
                $.each(messageData, function(index, messageData){
                    if(selectedUserId == messageData.msgfrom){

                        filteredArray.push(messageData);
                    }
                    console.log(JSON.stringify(filteredArray));
                    showMessage(filteredArray);
                });
            }
        });
    }

    function changeImage(element,rowId) {
        element.src = element.bln ? "<?php echo AppUtility::getHomeURL() ?>img/flagempty.gif" : "<?php echo AppUtility::getHomeURL() ?>img/flagfilled.gif";
        element.bln = !element.bln;
        var row = {rowId: rowId};

        jQuerySubmit('change-image-ajax', row , 'changeImageSuccess');


    }
    function changeImageSuccess(response)
    {


    }
</script>