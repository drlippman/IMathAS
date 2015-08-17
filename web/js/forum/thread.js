$(document).ready(function ()
{

    var forumid= $('#forumid').val();
    var isValue = -1;
    var courseid = $("#courseid").val();
    $('.select_option').val(-1);
    $("#show-all-link").hide();
    var page = $('#page').val();
    $('#result').hide();
    $('.forumResult').hide();
    if(page)
    {
        limitToNew();
    }else
    {
        jQuerySubmit('get-thread-ajax',{forumid: forumid,isValue:isValue },'threadSuccess');
    }
    limitToTagShow();

    $('.select_option').click(function(){
        selected = $('.select_option :selected').val();
        if(selected == 0)
        {
            var forumid= $('#forumid').val();
            window.location = "list-post-by-name?cid="+courseid+"&forumid="+forumid;

        }
        else if(selected == 1)
        {

            $('.forum-table').DataTable().destroy();
            var isValue = 1;
            var forumid= $('#forumid').val();
            var thread = {forumid: forumid , isValue: isValue};
            jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

        }
        else if(selected == 2)
        {

            $('.forum-table').DataTable().destroy();
            var isValue = 2;
            var forumid= $('#forumid').val();
            var thread = {forumid: forumid , isValue: isValue};
            jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

        }
        else if(selected == 3)
        {
            window.location.reload();
        }
    });
    $('#change-button').click(function(){
        var searchText = $('#search_text').val();
        var courseid = $('#courseid').val();
        if(searchText.length>0)
        {
            if(searchText.match(/^[a-z A-Z 0-9-]+$/))
            {
            $('#flash-message').hide();
                if(document.getElementById('searchAll').checked)
                {
                    $('#searchpost').show();
                    $('#flash-message').hide();

                    jQuerySubmit('get-search-post-ajax',{search: searchText, courseid: courseid},'postSearchSuccess');
                }
                else
                {
                    $('#searchpost').show();
                    $('#flash-message').hide();
                    jQuerySubmit('get-only-post-ajax',{search: searchText, courseid: courseid,forumid:forumid},'postSearchUnchecked');

                }
            }
            else
            {
                $('#flash-message').show();
                $('#flash-message').html("<div class='alert alert-danger'>Search text can contain only alphanumeric values");
                $('#search_text').val(null);
            }
        }else
        {
            $('#flash-message').show();
            $('#flash-message').html("<div class='alert alert-danger'>Search text cannot be blank");
        }

    });

 });
var hideLink =0;
function postSearchSuccess(response)
{
    response = JSON.parse(response);

    if (response.status == 0)
    {
        $('#searchpost').empty();
        $('#data').empty();
        var courseid = $('#courseid').val();
        var postData = response.data.data;
        $.each(postData, function(index, Data)
        {
            var result = Data.message.replace(/<[\/]{0,1}(p)[^><]*>/ig,"");
            var html = "<div class='block'>";
            html += "<b><label  class='subject'>"+Data.subject+"</label></b>";
            html += "&nbsp;&nbsp;&nbsp;in(&nbsp;<label class='forumname'>"+Data.forumName+"</label>)";
            html += "<br/>Posted by:&nbsp;&nbsp;<label class='postedby'>"+Data.name+"</label>";
            html += "&nbsp;&nbsp;<label id='postdate'>"+Data.postdate+"</label>";
            html += "</div><div class=blockitems>";
            html += "<label id='message'>"+result+"</label>";
            html += "<p><a href='post?courseid=" + courseid + "&threadid=" + Data.threadId +"&forumid="+ Data.forumIdData+"'</a>Show full thread</p>";
            html += "</div>\n";
            $('#searchpost').append(html);
        });
        $('.threadDetails').hide();
        $('.forumResult').show();
        $('#noThread').hide();
    }
    else
    {
        $('#searchpost').hide();
        $('.forumResult').hide();
        var msg ="No result found for your search";
        CommonPopUp(msg);
    }
}

function postSearchUnchecked(response)
{
    response = JSON.parse(response);

    if (response.status == 0)
    {
        $('#searchpost').empty();
        $('#data').empty();
        var courseid = $('#courseid').val();
        var postData = response.data.data;
        $.each(postData, function(index, Data)
        {
            var result = Data.message.replace(/<[\/]{0,1}(p)[^><]*>/ig,"");
            var html = "<div class='block'>";
            html += "<b><label  class='subject'>"+Data.subject+"</label></b>";
            html += "&nbsp;&nbsp;&nbsp;in(&nbsp;<label class='forumname'>"+Data.forumName+"</label>)";
            html += "<br/>Posted by:&nbsp;&nbsp;<label class='postedby'>"+Data.name+"</label>";
            html += "&nbsp;&nbsp;<label id='postdate'>"+Data.postdate+"</label>";
            html += "</div><div class=blockitems>";
            html += "<label id='message'>"+result+"</label>";
            html += "<p><a href='post?courseid=" + courseid + "&threadid=" + Data.threadId +"&forumid="+ Data.forumIdData+"'</a>Show full thread</p>";
            html += "</div>\n";
            $('#searchpost').append(html);
        });
        $('.threadDetails').hide();
        $('.forumResult').show();
        $('#noThread').hide();
    }
    else
    {
        $('.forumResult').hide();
        $('#searchpost').hide();
        var msg ="No result found for your search";
        CommonPopUp(msg);
    }
}
var newCount=0;
var count;

function threadSuccess(response)
{
    response = JSON.parse(response);
    var fid = $('#forumid').val();
    var courseId = $('#courseid').val();
    var settings = $('#settings').val();
    var unRead = $('#un-read').val();
    var isModifyThread = ( settings & 2) == 2;
    var isRemoveThread = ( settings & 4) == 4;
    if (response.status == 0) {
        var threads = response.data.threadArray;
        var uniquesDataArray = response.data.uniquesDataArray;
        var isValue = response.data.isValue;
        var checkFlagValue;
        var html = "";
        $.each(threads, function (index, thread) {
            if (fid == thread.forumiddata) {
                count =0;
                $.each(threads,function (index,data)
                {
                    if(thread.threadId == data.threadId)
                    {
                        count++;
                    }
                });
                count--;
                if(thread.parent == 0)
                {
                    if(thread.isanon == 0)
                    {
                        if(((thread .postdate >= thread.lastview || thread.lastview==0 ) && thread.currentUserId != thread.postUserId) || unRead == thread.threadId)
                        {

                            html += "<tr> <td><div class='main-name-div'><div class='user-name pull-left'><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"&forumid="+fid+"'>" + (thread.subject) +"</a></div><div class='new-tag pull-right '>New</div></div><br> "+ thread.name+"</td>";
                            newCount++;
                        }else
                        {
                            html += "<tr> <td><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"&forumid="+fid+"'>" + (thread.subject) +"<br> </a>"+ thread.name+"</td>";
                        }
                    }else
                    {
                        if((thread .postdate >= thread.lastview || thread.lastview==0 ) && thread.currentUserId != thread.postUserId)
                        {
                            html += "<tr> <td><div class='main-name-div'><div class='user-name pull-left'><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"&forumid="+fid+"'>" + (thread.subject) +"</a></div><div class='new-tag pull-right '>New</div></div><br>Anonymous </td>";
                            newCount++;
                        }else
                        {
                            html += "<tr> <td><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"&forumid="+fid+"'>" + (thread.subject) +"<br></a>Anonymous </td>";
                        }
                    }

                    if(thread.groupSetId > 0 && thread.userright > 10){
                        html += "<td>Non-group-specific</td>";
                    }
                    if(count >= 0){
                    html += "<td>" + count + "</td>";}
                    var uniqueView = thread.countArray;
                    uniqueView--;
                        if(uniqueView == -1){
                            uniqueView = '';
                        }
                        if (thread.userright >= 20) {
                            html += "<td><a href='#' name='view-tabs' data-var='" + thread.threadId + "' >" + thread.views + "(" + uniqueView + ")" + "</a></td>";
                        } else {
                            html += "<td>" + thread.views + "(" + uniqueView + ")" + "</td>";
                        }
                        html += "<td>" + thread .postdate + "</td>";
                        if (thread.tagged != 1 && thread.posttype == 0 )
                        {
                                html += "<td><div class='btn-group'> <a class='btn btn-primary flag-btn' onclick='changeImage(this," + true + "," + thread.threadId+" )'>" +
                                "<i class='fa fa-flag-o'></i> Flag</a><a class='btn btn-primary dropdown-toggle ' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>" +
                                "<ul class='dropdown-menu'>" ;
                                 if(thread.userright > 10)
                                 {
                                    html+="<li><a href='move-thread?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-scissors'></i>&nbsp;Move</a></li>" +
                                    "<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>" +
                                    "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                }
                                 else if(thread.currentUserId == thread.postUserId)
                                 {
                                     if(isModifyThread && thread.isReplies == 0 && isRemoveThread)
                                     {
                                         html+="<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>" +
                                             "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                     }else if(isModifyThread)
                                     {
                                         html+="<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>";
                                     }
                                     else if(thread.isReplies == 0 && isRemoveThread)
                                     {
                                         html += "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                     }
                                     else {html += "<li><a href='#'><i class='fa fa-exclamation'></i></i></i>&nbsp;No Action Allowed</a></li>";}

                                 }
                                 else
                                 {
                                     html += "<li><a href='#'><i class='fa fa-exclamation'></i></i>&nbsp;No Action Allowed</a></li>";
                                 }
                         }
                         else if(thread.posttype == 0 )
                        {

                                html += "<td><div class='btn-group'> <a class='btn btn-primary flag-btn' onclick='changeImage(this," + true + "," + thread.threadId +" )' >"+
                                "<i class='fa fa-flag'></i> Unflag</a><a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>" +
                                "<ul class='dropdown-menu'>";
                                if(thread.userright > 10) {
                                    html+="<li><a href='move-thread?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-scissors'></i>&nbsp;Move</a></li>" +
                                        "<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>" +
                                        "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                }
                                else if(thread.currentUserId == thread.postUserId)
                                {
                                    if(isModifyThread && thread.isReplies == 0 && isRemoveThread)
                                    {
                                        html+="<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>" +
                                            "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                    }else if(isModifyThread){
                                        html+="<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>";
                                    }
                                    else if(thread.isReplies == 0 && isRemoveThread){
                                        html += "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                                    }
                                    else {
                                        html += "<li><a href='#'><i class='fa fa-exclamation'></i></i>&nbsp;No Action Allowed</a></li>";
                                    }

                                }
                                else {

                                    html += "<li><a href='#'><i class='fa fa-exclamation'></i></i>&nbsp;No Action Allowed</a></li>";
                                }
                        }
                        else
                        {
                            html += "<td><div class='btn-group'> <a class='btn btn-primary flag-btn disable-btn-not-allowed'>"+
                                " No Flag</a><a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'><span class='fa fa-caret-down '></span></a>" +
                                "<ul class='dropdown-menu'>";
                            if(thread.userright > 10) {
                                html+="<li><a href='move-thread?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-scissors'></i>&nbsp;Move</a></li>" +
                                    "<li><a class ='roster-make-excetion' href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>" +
                                    "<li><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'><i class='fa fa-trash-o'></i></i>&nbsp;Remove</a></li>";
                            }else
                            {
                                html+="<li><a href='#' class='disable-btn-not-allowed'><i class='fa fa-exclamation'></i></i>&nbsp;No Action Allowed</a></li>";

                            }
                        }
                }
            }
        });
        $('.forum-table-body').empty();
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable({"ordering": false ,bPaginate: false});
        if(isValue == 2)
        {
            $('#limit-to-new-link').hide();

        }else{
            if(newCount > 0)
            {
                $('#limit-to-new-link').show();
                $('#markRead').show();
            }
            else{
                $('#limit-to-new-link').hide();
                $('#markRead').hide();
            }
        }

    }
    else if (response.status == -1)
    {
        $('#markRead').hide();
    }
    if(isValue == 3)
    {
        window.location.reload();

    }


    $("a[name=tabs]").on("click", function () {
        var threadsid = $(this).attr("data-var");
        var checkPostOrThread = 1;
        var html = '<div><p>Are you SURE you want to remove this thread and all replies?</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "confirm": function () {
                    $(this).dialog("close");
                    var threadId = threadsid;

                    jQuerySubmit('mark-as-remove-ajax', {threadId:threadId,checkPostOrThread:checkPostOrThread}, 'markAsRemoveSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });

    $("a[name=view-tabs]").on("click", function () {
        var threadsid = $(this).attr("data-var");
        var html = '<div><p>Thread Views : </p></div><p>';
        html +=  '<span class="col-lg-11" >Name     LastView </span><br>';
        $.each(uniquesDataArray, function (index, uniqueEntry) {

            if(threadsid == uniqueEntry.threadId){
                html += '<span class="col-lg-12 pull-left " >'+ uniqueEntry.name +''+uniqueEntry.lastView+'</span><br>';
            }

        });

        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });

    });
}

var isValue;
function changeImage(element,checkFlagValue, rowId) {

    var userId = $("#user-id").val();

    var row = {rowId: rowId,userId:userId};
    jQuerySubmit('change-image-ajax', row,'flagResponse');

}


function flagResponse()
{
    window.location.reload();
}

function markAsRemoveSuccess(response) {
    var forumid = $("#forumid").val();
    var courseid = $("#courseid").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forumid="+forumid;
    }
}
function limitToTagShow() {

    $("#show-all-link").click(function () {
        $('.forum-table').DataTable().destroy();
        $("#limit-to-tag-link").show();
        $("#show-all-link").hide();
        isValue = 0;
        hideLink = 0;
        var forumid= $('#forumid').val();
        var thread = {forumid: forumid , isValue: isValue,hideLink:hideLink};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

    });
    $('#markRead').click(function(){
        var isValue = 3;
        var forumid= $('#forumid').val();
        $('.forum-table').DataTable().destroy();
        var thread = {forumid: forumid , isValue: isValue};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');
    });


}
function limitToNew()
{
    $('.forum-table').DataTable().destroy();
    $("#limit-to-tag-link").hide();
    $('#limit-to-new-link').hide();
    $("#show-all-link").show();
    var isValue = 2;
    var forumid= $('#forumid').val();
    var thread = {forumid: forumid , isValue: isValue};
    jQuerySubmit('get-thread-ajax',thread,'threadSuccess');
}
