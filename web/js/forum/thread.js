$(document).ready(function ()
{
    var forumid= $('#forumid').val();
    var isValue = -1;
    $("#show-all-link").hide();
    $('#result').hide();
    $('#noThread').hide();
    $('.forumResult').hide();
    jQuerySubmit('get-thread-ajax',{forumid: forumid,isValue:isValue },'threadSuccess');
    limitToTagShow();

    $('#change-button').click(function(){


        var searchText = $('#searchText').val();
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
                $('#searchText').val(null);
            }
        }else
        {
            $('#flash-message').show();
            $('#flash-message').html("<div class='alert alert-danger'>Search text cannot be blank");
        }

    });

 });
function postSearchSuccess(response)
{
    response = JSON.parse(response);

    if (response.status == 0)
    {
        $('#searchpost').empty();
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
    var courseId = $('#course-id').val();
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
                if(thread.parent == 0){

                    html += "<tr> <td><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"&forumid="+fid+"'>" + (thread.subject) +"&nbsp;</a>"+ thread.name+" </td>";
                    if (thread.tagged == 0 && thread.posttype == 0 ) {
                        html += " <td> <img src='../../img/flagempty.gif'  onclick='changeImage(this," + false + "," + thread.threadId + ")'></td> ";
                    }
                    else if(thread.posttype == 0 ){
                        html += " <td> <img src='../../img/flagfilled.gif'  onclick='changeImage(this," + true + "," + thread.threadId + ")'></td> ";
                    }else {
                        html += " <td> - </td> ";
                    }
                    if(thread.userright > 10) {
                        html += " <td><a href='move-thread?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'>Move</a> <a href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'>Modify</a><a href='#' name='tabs' data-var='" + thread.threadId + "' class='mark-remove'> Remove </a></td> ";
                    }else if(thread.currentUserId == thread.postUserId){
                        html += " <td> <a href='modify-post?forumId=" + thread.forumiddata + "&courseId=" + courseId + "&threadId=" + thread.threadId + "'>Modify</a> </td> ";
                    }else { html += " <td> - </td> "; }
                    if(count >= 0){
                    html += "<td>" + count + "</td>";}
                    $.each(thread.countArray, function (index, count) {
                        count.usercount--;
                        if(count.usercount == -1){
                            count.usercount = '';
                        }
                        if (thread.userright >= 20) {
                            html += "<td><a href='#' name='view-tabs' data-var='" + thread.threadId + "' >" + thread.views + "(" + count.usercount + ")" + "</a></td>";
                        } else {
                            html += "<td>" + thread.views + "(" + count.usercount + ")" + "</td>";
                        }
                    });


                    if(thread .postdate >= thread.lastview && thread.currentUserId != thread.postUserId)
                    {
                           html += "<td>" + thread .postdate + "&nbsp;<span style='color: red'>New</span></td>";
                           newCount++;

                    }
                    else
                    {
                        html += "<td>" + thread .postdate + "</td>";
                    }

                }
            }
        });
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable({"ordering": false});

    }
    else if (response.status == -1) {

        $('#data').hide();
        $('#noThread').show();
    }
    if(isValue == 3)
    {
        window.location.reload();

    }
    $("a[name=tabs]").on("click", function () {
        var threadsid = $(this).attr("data-var");
        var checkPostOrThread =1;
        var html = '<div><p>Are you sure? This will remove your thread.</p></div>';
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
            console.log(uniqueEntry);
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

    if(checkFlagValue == false){
        element.src = element.bln ? '../../img/flagempty.gif' : '../../img/flagfilled.gif';
        element.bln = !element.bln;
    }
    if(checkFlagValue ==true ){
        element.src = element.bln ? '../../img/flagfilled.gif' : '../../img/flagempty.gif';
        element.bln = !element.bln;
    }
    var row = {rowId: rowId};
    jQuerySubmit('change-image-ajax', row, 'changeImageSuccess');

}
function changeImageSuccess(response) {
}
function markAsRemoveSuccess(response) {
    var forumid = $("#forumid").val();
    var courseid = $("#course-id").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forumid="+forumid;
    }
}
function limitToTagShow() {

    $("#limit-to-tag-link").click(function () {
        $(".forum-table-body").empty();
        $("#limit-to-tag-link").hide();
        $('#limit-to-new-link').hide();
        $("#show-all-link").show();
        var isValue = 1;
        var forumid= $('#forumid').val();
        var thread = {forumid: forumid , isValue: isValue};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

    });
    $("#show-all-link").click(function () {
        $(".forum-table-body").empty();
        $("#limit-to-tag-link").show();
        $("#show-all-link").hide();
        $("#limit-to-new-link").show();
        isValue = 0;
        var forumid= $('#forumid').val();
        var thread = {forumid: forumid , isValue: isValue};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

    });
    $("#limit-to-new-link").click(function () {
        $(".forum-table-body").empty();
        $("#limit-to-tag-link").hide();
        $('#limit-to-new-link').hide();
        $("#show-all-link").show();
        var isValue = 2;
        var forumid= $('#forumid').val();
        var thread = {forumid: forumid , isValue: isValue};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

    });

    $('#markRead').click(function(){
        var isValue = 3;
        var forumid= $('#forumid').val();
        var thread = {forumid: forumid , isValue: isValue};
        jQuerySubmit('get-thread-ajax',thread,'threadSuccess');
    });
}
