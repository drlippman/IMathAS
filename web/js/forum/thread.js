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
        //jQuerySubmit('get-thread-ajax',{forumid: forumid,isValue:isValue },'threadSuccess');
    }
    limitToTagShow();
        $('.select_option').click(function(){
        selected = $('.select_option :selected').val();
        page = $('#page').val();
        var forumid= $('#forumid').val();
        if(selected == 0)
        {

            window.location = "list-post-by-name?page="+page+"&cid="+courseid+"&forumid="+forumid;
        }
        else if(selected == 1)
        {

            $('.forum-table').DataTable().destroy();
                window.location = "thread?page=-2&cid="+courseid+"&forum="+forumid;
        }
        else if(selected == 2)
        {
            $('.forum-table').DataTable().destroy();
            window.location = "thread?page=-1&cid="+courseid+"&forum="+forumid;
        }
        else if(selected == 3)
        {
            console.log(forumid);
            window.location = "thread?page=1&cid="+courseid+"&forum="+forumid;
        }
    });
    $('#change-button111').click(function(){
        var searchText = $('#search_text').val();
        var courseid = $('#courseid').val();
        if(searchText.length>0)
        {
            alert('not blank');
            if(searchText.match(/^[a-z A-Z 0-9-]+$/))
            {
            $('#flash-message').hide();
                if(document.getElementById('searchAll').checked)
                {
                    $('#searchpost').show();
                    $('#flash-message').hide();
                    $('#myform').submit();
                    //jQuerySubmit('get-search-post-ajax',{search: searchText, courseid: courseid},'postSearchSuccess');
                }
                else
                {
                    $('#searchpost').show();
                    $('#flash-message').hide();
                    $('#myform').submit();
                    //jQuerySubmit('get-only-post-ajax',{search: searchText, courseid: courseid,forumid:forumid},'postSearchUnchecked');
                //
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
            alert('blank');
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
        //jQuerySubmit('get-thread-ajax',thread,'threadSuccess');

    });
    $('#markRead').click(function(){
        var isValue = 3;
        var forumid= $('#forumid').val();
        $('.forum-table').DataTable().destroy();
        var thread = {forumid: forumid , isValue: isValue};
        //jQuerySubmit('get-thread-ajax',thread,'threadSuccess');
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
    //jQuerySubmit('get-thread-ajax',thread,'threadSuccess');
}

$("a[name=tabs]").on("click", function (event) {
    event.preventDefault();
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


function toggletagged(threadid) {
    var trchg = document.getElementById("tr"+threadid);
    if (trchg.className=="tagged") {
        submitTagged(threadid,0);
    } else {
        submitTagged(threadid,1);
    }
    return false;
}

function submitTagged(thread,tagged) {
    url = AHAHsaveurl + '&threadid='+thread+'&tagged='+tagged;
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (typeof req != 'undefined') {
        req.onreadystatechange = function() {ahahDone(url, thread, tagged);};
        req.open("GET", url, true);
        req.send("");
    }
}