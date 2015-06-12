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
    response = JSON.parse(response);
    messageData = response.data;
    if(response.status == 0)
    {
        var filterArrayForUser = [];
        $.each(response.data, function(index, messageData){
                filterArrayForUser.push(messageData.msgto);
        });
    }else if(response.status == -1)
    {

    }
    showMessage(response.data, response.status);
}

function showMessage(messageData, status)
{
    var cid = $(".send-course-id").val();
    var html = "";
    var htmlCourse ="";
    if(status == 0){
        $.each(messageData, function(index, msg){
            html += "<tr> <td><input type='checkbox' name='msg-check' value='"+msg.id+"' class='message-checkbox-"+msg.id+"' ></td>";
            html += "<td><a href='view-message?message=1&id="+msg.id+"&cid="+cid+"'> "+msg.title+"</a></td>";
            html += "<td>"+msg.FirstName.substr(0,1).toUpperCase()+ msg.FirstName.substr(1)+" "+msg.LastName.substr(0,1).toUpperCase()+ msg.LastName.substr(1)+"</td>";
            if(msg.isread==0)
            {
                html+="<td>No</td>";
            }
            else{
                html+="<td>Yes</td>"
            }
            html += "<td>"+msg.senddate+"</td>";
        });
    }

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

function getCourseSuccess(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var courseData = result.data;
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
            var html = '<div><p>Are you sure ? you want to Remove.</p></div>';

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
                    "Confirm": function () {
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
function markDeleteSuccess(){}

function getUserSuccess(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var userData = result.data;
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
            showMessage(messageData, status = 0);
        }else {
            $.each(messageData, function(index, msg){
                if(selectedUserId == msg.msgto){
                    filteredArray.push(msg);
                }
            });
            showMessage(filteredArray, status = 0);
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
            var html = '<div><p>Are you sure ? you want to Unsend. </p></div>';

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
                    "Confirm": function () {
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
function markUnsendSuccess(){}

function filterByCourse()
{
    $('#course-sent-id').on('change', function() {
        var filteredArray = [];
        var selectedCourseId = this.value;
        if(selectedCourseId == 0 ){
            showMessage(messageData, status = 0);
        }else{
            $.each(messageData, function(index, messageData){
                if(selectedCourseId == messageData.courseid ){
                    filteredArray.push(messageData);
                }
                showMessage(filteredArray, status = 0);
            });
        }
    });
}
