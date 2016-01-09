$(document).ready(function () {
    var cid = $(".send-course-id").val();
    var userId = $(".send-user-id").val();
    var inputData = {cid: cid, userId: userId};
//    jQuerySubmit('display-sent-message-ajax',inputData, 'showMessageSuccess');
    selectCheckBox();
    jQuerySubmit('get-sent-course-ajax',  inputData, 'getCourseSuccess');
    jQuerySubmit('get-sent-user-ajax',  inputData, 'getUserSuccess');
    checkUncheckHeaderCheckbox();
});

var messageData;
var cid = $(".send-course-id").val();
var selectedUserId = $('#user-sent-id').val();
var selectedCourseId;
var isModifiedArray = [];
function changeMessageStatus(){

    var with_selected = $('.with-selected :selected').val();
    if(with_selected  == 1)
    {
        markSentDelete()
    }
    else if(with_selected  == 2)
    {
        markUnsend();
    }
    $('.with-selected').val('0');
}

function createTableHeader()
{
//    var html = "<div class='message-div'><table id='message-table-show display-message-table' class='display table table-bordered table-striped table-hover data-table'>";
//    html += "<thead><tr><th><div class='checkbox override-hidden'><label><input type='checkbox' id='message-header-checkbox' name='header-checked' value=''><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr></thead>";
//    html += "<tbody class='message-table-body'></tbody></table></div>";
    $('.message-div').append(html);
}
function showMessageSuccess(response)
{
    response = JSON.parse(response);
    messageData = response.data;
    if(response.status == 0)
    {
        var filterArrayForUser = [];
        var filteredArray = [];
        $.each(response.data, function(index, messageData){
                filterArrayForUser.push(messageData.msgto);
            if(cid == messageData.courseid){
                filteredArray.push(messageData);
            }
        });
    }else if(response.status == -1)
    {

    }
    showMessage(filteredArray, response.status);
}

function showMessage(messageData, status)
{
    var cid = $(".send-course-id").val();
    var html = "";
    var htmlCourse ="";
    if(status == 0){
        $.each(messageData, function(index, msg){
            html += "<tr class='message-checkbox-'" + msg.id + "><td><div class='checkbox override-hidden'><label>" +
                "<input type='checkbox' name='msg-check' value='"+msg.id+"' class='message-checkbox-"+msg.id+"' ><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></td>";
            html += "<td><a href='view-message?message=1&msgid="+msg.id+"&cid="+cid+"'> "+msg.title+"</a></td>";
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
//    createTableHeader();
    $(".message-table-body").append(html);
    $('.display-message-table').DataTable({"bPaginate": true});
}

function checkUncheckHeaderCheckbox(){
    $(document).on('click', '.message-table-body input:checkbox', function() {
        totalMessagesSize = $('.message-table-body input:checkbox').size();
        checkedMessagesSize = $('.message-table-body input[name="msg-check"]:checked').size();
        $('#message-header-checkbox').prop('checked', false);
        if(totalMessagesSize == checkedMessagesSize){
            $('#message-header-checkbox').prop('checked', true);
        }
    })
}

function selectCheckBox(){
    $(document).on('click', '#message-header-checkbox', function() {
        if($(this).prop("checked") == true){
            $('.message-table-body input:checkbox').each(function () {
                $(this).prop('checked', true);
                if($.inArray($(this).val(), isModifiedArray) != -1){
                    $(this).closest('tr').remove();
                }
            })
        }
        else if($(this).prop("checked") == false){
            $('.message-table-body input:checkbox').each(function () {
                $(this).prop('checked', false);
                if($.inArray($(this).val(), isModifiedArray) != -1){
                    $(this).closest('tr').remove();
                }
            })
        }
    });
}

function getCourseSuccess(response)
{
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
        if(courseData.id != cid) {
            html += "<option value = " + courseData.id + ">" + courseData.name.substr(0, 1).toUpperCase() + courseData.name.substr(1) + "</option>"
        }else{
            html += "<option selected='selected' value = " + courseData.id + ">" + courseData.name.substr(0, 1).toUpperCase() + courseData.name.substr(1) + "</option>"
        }
    });
    $(".show-course").append(html);
}
function markSentDelete()
{
        var markArray = [];
        $('.message-table-body input[name="msg-check"]:checked').each(function () {
            markArray.push($(this).val());
            isModifiedArray.push($(this).val());
        });
        if(markArray.length!=0) {
            var html = '<div><p>Are you sure ? you want to Remove.</p></div>';

            var cancelUrl = $(this).attr('href');
            e.preventDefault;
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
            var msg ="Select atleast one message to Remove";
            CommonPopUp(msg);
        }


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
        html += "<option value = "+userData.id+">"+userData.LastName.substr(0,1).toUpperCase()+ userData.LastName.substr(1) +", "+userData.FirstName.substr(0,1).toUpperCase()+ userData.FirstName.substr(1)+"</option>"
    });
    $(".show-users").append(html);

}


function markUnsend()
{
    var markArray = [];
        $('.message-table-body input[name="msg-check"]:checked').each(function () {
            markArray.push($(this).val());
        });
        if(markArray.length!=0) {
            var html = '<div><p>Are you sure ? you want to Delete. </p></div>';

            var cancelUrl = $(this).attr('href');
            e.preventDefault;
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
            var msg ="Select atleast one message to Delete";
            CommonPopUp(msg);
        }

}
function markUnsendSuccess(){
}

