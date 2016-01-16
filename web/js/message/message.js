$(document).ready(function () {
    $("#show-all-link").hide();
    var cid = $(".send-msg").val();
    var isImportant = $('.is-important').val();
    var ShowRedFlagRow = -1;
    var isNewMessage = $(".msg-type").val();
    var userId = $(".send-userId").val();
    if(isImportant == 1){
        ShowRedFlagRow = 1;
        $("#limit-to-tag-link").hide();
        $("#show-all-link").show();
    }
    var allMessage = {cid: cid, userId: userId, ShowRedFlagRow: ShowRedFlagRow, showNewMsg: isNewMessage};
//    jQuerySubmit('display-message-ajax', allMessage, 'showMessageSuccess');
//    jQuerySubmit('get-course-ajax', allMessage, 'getCourseSuccess');
//    jQuerySubmit('get-user-ajax', allMessage, 'getUserSuccess');

    selectCheckBox();
    checkUncheckHeaderCheckbox();
//    filterByCourse();
});

var messageData;
var cid = $(".send-msg").val();
var selectedUserId = $('#user-id').val();
var selectedCourseId ;
var courseInfo = [];
var isModifiedArray = [];
function changeMessageStatus(){

    var with_selected = $('.with-selected :selected').val();
    if(with_selected  == 1)
    {
        markAsUnread();
    }
    else if(with_selected  == 2)
    {
        markAsRead();
    }else if(with_selected  == 3){
        markAsDelete();
    }
    $('.with-selected').val('0');
}
function showMessageSuccess(response) {
    response = JSON.parse(response);
    messageData = response.data;
    if(response.status == 0)
    {
        var filterArrayForUser = [];
        var filteredArray = [];
        $.each(response.data, function (index, msg) {
            filterArrayForUser.push(msg.msgfrom);
            if(cid == msg.courseid){
                filteredArray.push(msg);
            }
        });
    }
    else if(response.status == -1)
    {

    }
//    showMessage(filteredArray, response.status);
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

function getCourseSuccess(response) {
    var result = JSON.parse(response);
    var course = result.data;
    if (result.status == 0) {
//        courseDisplay(course);
        filterByCourse();
    }
}

//function courseDisplay(courseData) {
//    var html = "";
//    $.each(courseData, function (index, courseData) {
//        if(courseData.courseId != cid){
//            html += "<option value = " + courseData.courseId + ">" + courseData.courseName.substr(0, 1).toUpperCase() + courseData.courseName.substr(1) + "</option>"
//        }else{
//            html += "<option selected='selected' value = " + courseData.courseId + ">" +  courseData.courseName.substr(0, 1).toUpperCase() + courseData.courseName.substr(1) + "</option>"
//        }
//    });
//    $(".show-course").append(html);
//}

function markAsUnread() {
    var markArray = [];
    $('.message-table-body input[name="msg-check"]:checked').each(function () {
        $(this).closest('tr').css('font-weight', 'bold');
        markArray.push($(this).val());
        $(this).prop('checked', false);
    });
    $('input[name = "header-checked"]:checked').prop('checked', false);
    if( markArray.length !=0)
    {
        var readMsg = {checkedMsg: markArray};
        jQuerySubmit('mark-as-unread-ajax', readMsg, { });
    }
    else
    {
        var msg ="Select atleast one message to unread";
        CommonPopUp(msg);
    }

}

function markAsRead() {
    var markArray = [];
    $('.message-table-body input[name="msg-check"]:checked').each(function () {
        markArray.push($(this).val());
        $(this).closest('tr').css('font-weight', 'normal');
        $(this).prop('checked', false);
    });
    $('input[name = "header-checked"]:checked').prop('checked', false);
    if( markArray.length !=0){
        var readMsg = {checkedMsg: markArray};
        jQuerySubmit('mark-as-read-ajax', readMsg,{ });
    }
    else {
        var msg ="Select atleast one message to read";
        CommonPopUp(msg);
    }
}
function filterByCourse() {
    $('#course-id').on('change', function () {
        var filteredArray = [];
        selectedCourseId = this.value;
        if (selectedCourseId == 0 && selectedUserId == 0) {
            showMessage(messageData, status = 0);
        } else if(selectedCourseId == 0 && selectedUserId != 0){
            $.each(messageData, function (index, msg) {
                if (selectedUserId == msg.msgfrom) {
                    filteredArray.push(msg);
                }
                showMessage(filteredArray, status = 0);
            });
        } else if(selectedCourseId != 0 && selectedUserId == 0){
            $.each(messageData, function (index, msg) {
                if (selectedCourseId == msg.courseid) {
                    filteredArray.push(msg);
                }
                showMessage(filteredArray, status = 0);
            });
        } else {
            $.each(messageData, function (index, msg) {
                if (selectedCourseId == msg.courseid) {
                    if (selectedUserId == msg.msgfrom) {
                        filteredArray.push(msg);
                    }
                }
                showMessage(filteredArray, status = 0);
            });
        }
    });
}

function filterByUser() {
    $('#user-id').on('change', function () {
        var filteredArray = [];
        selectedUserId = this.value;
        selectedCourseId = $('#course-id').val();
        if (selectedCourseId == 0 && selectedUserId == 0) {
            showMessage(messageData, status = 0);
        } else if(selectedCourseId == 0 && selectedUserId != 0){
            $.each(messageData, function (index, msg) {
                if (selectedUserId == msg.msgfrom) {
                    filteredArray.push(msg);
                }
                showMessage(filteredArray, status = 0);
            });
        } else if(selectedCourseId != 0 && selectedUserId == 0){
            $.each(messageData, function (index, msg) {
                if (selectedCourseId == msg.courseid) {
                    filteredArray.push(msg);
                }
                showMessage(filteredArray, status = 0);
            });
        } else {
            $.each(messageData, function (index, msg) {
                if (selectedCourseId == msg.courseid) {
                    if (selectedUserId == msg.msgfrom) {
                        filteredArray.push(msg);
                    }
                }
                showMessage(filteredArray, status = 0);
            });
        }
    });
}

function getUserSuccess(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var userData = result.data;
        userDisplay(userData);
        filterByUser();
    }
}

function userDisplay(userData) {
    var html = "";
    $.each(userData, function (index, userData) {
        html += "<option value = " + userData.id + ">"+ userData.LastName.substr(0,1).toUpperCase()+ userData.LastName.substr(1) +" "+userData.FirstName.substr(0,1).toUpperCase()+ userData.FirstName.substr(1) + "</option>"
    });
    $(".show-users").append(html);
}

function markAsDelete() {
    var markArray = [];
    $('.message-table-body input[name="msg-check"]:checked').each(function () {
        markArray.push($(this).val());
    });
    if (markArray.length != 0) {
        var html = '<div><p>Are you sure? This will delete your message from</p>' +
            '<p>Inbox.</p></div>';
        var cancelUrl = $(this).attr('href');
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    uncheckAllCheckbox(false);
                    return false;
                },
                "Confirm": function () {
                    uncheckAllCheckbox(true);
                    $(this).dialog("close");
                    var readMsg = {checkedMsg: markArray};
                    jQuerySubmit('mark-as-delete-ajax', readMsg,{});
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    }
    else {
        var msg ="Select atleast one message to delete";
        CommonPopUp(msg);
    }

}

function uncheckAllCheckbox(isRemoveMessageRows){
    $('.message-table-body input[name="msg-check"]:checked').each(function () {
        $(this).prop('checked', false);
        if(isRemoveMessageRows == true){
            $(this).closest('tr').remove();
            isModifiedArray.push($(this).val());
        }
    });
    $('input[name = "header-checked"]:checked').prop('checked', false);
}

function changeImage(element, temp, rowId) {
    if(temp == false){
        element.src = element.bln ? "../../img/flagempty.gif" : "../../img/flagfilled.gif";
        element.bln = !element.bln;
    }
    if(temp ==true ){
        element.src = element.bln ? "../../img/flagfilled.gif" : "../../img/flagempty.gif";
        element.bln = !element.bln;
    }
    var row = {rowId: rowId};
    jQuerySubmit('change-image-ajax', row,{ });
}

var picsize = 0;
function rotatepics() {
    picsize = (picsize+1)%3;
    picshow(picsize);
}
function picshow(size) {
    var course_id =  $( "#course-id" ).val();
    if (size==0) {
        els = document.getElementById("message-table display-message-table").getElementsByClassName("images");
        for (var i=0; i<els.length; i++) {
            els[i].style.display = "none";
        }
    } else {
        els = document.getElementById("message-table display-message-table").getElementsByClassName("images");
        for (var i=0; i<els.length; i++) {
            els[i].style.display = "inline";
            if (size==2) {
                els[i].style.width = "85px";
                els[i].style.height = "85px"
            }
            if (size==1) {
                els[i].style.width = "50px";
                els[i].style.height = "50px";
            }
        }
    }

}


