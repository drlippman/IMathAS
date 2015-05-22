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
            <select name="seluid" class="show-course form-control" id="course-sent-id">
            <option value="0">All Courses</option>

        </select>

        </span> <span class="col-md-2 select-text-margin" align="center"><b>By Recipient :</b></span>

        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="user-sent-id">
            <option value="0">Select a user</option>
            </select>

        </span></p>
    </div><br><br>
    <div>
        <p>check: <a id="uncheck-all-box" class="uncheck-all" href="#">None</a> /
            <a id="check-all-box" class="check-all" href="#">All</a>
            With Selected:
            <a class="btn btn-primary btn-sm"id="mark-sent-delete">Remove From Sent Message List</a>
            <a class="btn btn-primary btn-sm" id="mark-unsend">Unsend</a>
        </p>

    </div>

    <div class="message-div"></div>
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
        jQuerySubmit('get-sent-course-ajax',  inputData, 'getCourseSuccess');
        jQuerySubmit('get-sent-user-ajax',  inputData, 'getUserSuccess');
        markSentDelete();
        markUnsend();
    });

    var messageData;
    function createTableHeader()
    {
        var html = "<table id='message-table-show display-message-table' class='message-table-show display-message-table'>";
        html += "<thead><tr><th></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr></thead>"
        html += "<tbody class='message-table-body'></tbody></table>";
        $('.message-div').append(html);
    }
    function showMessageSuccess(response)
    {
       var filterArrayForUser = [];
        $.each(JSON.parse(response), function(index, messageData){
            $.each(messageData, function(index, msgData){
                filterArrayForUser.push(msgData.msgTo);
            });

        });
        var uniqueUserForFilter = filterArrayForUser.filter(function(itm,i,a){
            return i==a.indexOf(itm);
        });

        var htmlCourse = '';
        for(i = 0; i<uniqueUserForFilter.length; i++){
            htmlCourse += "<option value = messageData.msgTo>"+uniqueUserForFilter[i]+"</option>"

        }
     // $(".show-users").append(htmlCourse);

        var result = JSON.parse(response);
        if(result.status == 0)
        {
             messageData = result.messageData;
            showMessage(messageData);
        }
    }


    function showMessage(messageData)
    {
        var html = "";
        var htmlCourse ="";
        $.each(messageData, function(index, messageData){
            html += "<tr> <td><input type='checkbox' name='msg-check' value='"+messageData.id+"' class='message-checkbox-"+messageData.id+"' ></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message=1&id=')?>"+messageData.id+"'> "+messageData.title+"</a></td>";
            html += "<td>"+messageData.FirstName.substr(0,1).toUpperCase()+ messageData.FirstName.substr(1)+" "+messageData.LastName.substr(0,1).toUpperCase()+ messageData.LastName.substr(1)+"</td>";
            if(messageData.isread==0)
            {
                html+="<td>No</td>";
            }
            else{
                html+="<td>Yes</td>"
            }
            html += "<td>"+messageData.senddate+"</td>";
           });
        $('.message-div div').remove();
        createTableHeader();
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

    function    getCourseSuccess(response) {
        var result = JSON.parse(response);
        if (result.status == 0) {
            var courseData = result.courseData;
            courseDisplay(courseData);
            filterByCourse();
        }
    }

        function courseDisplay(courseData)
        {
            var html = "";
            $.each(courseData,function(index, courseData){
                html += "<option value = "+courseData.id+">"+courseData.name.substr(0,1).toUpperCase()+ courseData.name.substr(1)+"</option>"
            });
            $(".show-course").append(html);
        }
   function markSentDelete()
    {
        $("#mark-sent-delete").click(function(e){


            var markArray = [];
            $('.message-table-body input[name="msg-check"]:checked').each(function () {
                markArray.push($(this).val());
            });
            if(markArray.length!=0) {
                var html = '<div><p>Are you sure? This will delete your message from</p>' +
                    '<p>Inbox.</p></div>';

                var cancelUrl = $(this).attr('href');
                e.preventDefault();
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons: {
                        "Cancel": function () {

                            $(this).dialog('destroy').remove();
                            $('.message-table-body input[name="msg-check"]:checked').each(function () {

                                $(this).prop('checked', false);

                            });
                            return false;
                        },
                        "confirm": function () {
//                            window.location = cancelUrl;

                            $('.message-table-body input[name="msg-check"]:checked').each(function () {
                                $(this).prop('checked', false);
                                $(this).closest('tr').remove();
                            });
                            $(this).dialog("close");

                            var readMsg = {checkedMsgs: markArray};
                            jQuerySubmit('mark-sent-remove-ajax',readMsg,'markDeleteSuccess');
                            return true;
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    }

                });
            }
            else
            {
                alert("Nothing to Remove");
            }



        });

    }
    function markDeleteSuccess(){

    }

    function getUserSuccess(response) {
         var result = JSON.parse(response);
        if (result.status == 0) {
            var userData = result.userData;
            userDisplay(userData);
            filterByUser();
        }
    }

    function userDisplay(userData)
    {
        var html = "";
        $.each(userData,function(index, userData){
          html += "<option value = "+userData.id+">"+userData.FirstName.substr(0,1).toUpperCase()+ userData.FirstName.substr(1)+" "+userData.LastName.substr(0,1).toUpperCase()+ userData.LastName.substr(1)+"</option>"
        });
        $(".show-users").append(html);

    }
    function filterByUser()
    {
        $('#user-sent-id').on('change', function() {
            var filteredArray = [];
            var selectedUserId = this.value;
            if (selectedUserId == 0){
                showMessage(messageData);
            }else {
                $.each(messageData, function(index, messageData){
                    if(selectedUserId == messageData.msgto){

                        filteredArray.push(messageData);
                    }
                });
                showMessage(filteredArray);
            }
        });
    }

    function markUnsend() {

       $("#mark-unsend").click(function (e) {

           var markArray = [];
           $('.message-table-body input[name="msg-check"]:checked').each(function () {
               markArray.push($(this).val());
           });
           if(markArray.length!=0) {
               var html = '<div><p>Are you sure? This will delete your message from</p>'+
                   '<p>the receivers inbox and your sent list.</p></div>';

               var cancelUrl = $(this).attr('href');
               e.preventDefault();
               $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                   modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                   width: 'auto', resizable: false,
                   closeText: "hide",
                   buttons: {
                       "Cancel": function () {

                           $(this).dialog('destroy').remove();
                           $('.message-table-body input[name="msg-check"]:checked').each(function () {

                               $(this).prop('checked', false);

                           });
                           return false;
                       },
                       "confirm": function () {
//                            window.location = cancelUrl;

                           $('.message-table-body input[name="msg-check"]:checked').each(function () {
                               $(this).prop('checked', false);
                               $(this).closest('tr').remove();
                           });
                           $(this).dialog("close");

                           var readMsg = {checkedMsgs: markArray};
                           jQuerySubmit('mark-sent-unsend-ajax', readMsg, 'markUnsendSuccess');
                           return true;
                       }
                   },
                   close: function (event, ui) {
                       $(this).remove();
                   }

               });
           }
           else
           {
               alert("Nothing to unsend");
           }


        });
    }
    function markUnsendSuccess(){

    }

    function filterByCourse()
    {
        $('#course-sent-id').on('change', function() {
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

</script>
