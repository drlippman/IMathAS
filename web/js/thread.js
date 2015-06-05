
$(document).ready(function ()
{
    var forumid= $('#forumid').val();
    jQuerySubmit('get-thread-ajax',{forumid: forumid },'threadSuccess');
});


function threadSuccess(response)
{

    var result = JSON.parse(response);
    var fid = $('#forumid').val();
    var courseId = $('#course-id').val();

    if (result.status == 0) {

        var threads = result.threadData;
        var html = "";
        $.each(threads, function (index, thread) {
            if(fid == thread.forumiddata)
            {
                alert(thread.parent);
                if(thread.replyby == null)
                {
                    thread.replyby= 0;
                    if(thread.parent == 0)
                    {
                            html += "<tr> <td><a href='post?courseid="+courseId+"&threadid="+thread.threadId+"'>" +(thread.subject) +"</a> "+ thread.name+" <a href='move-thread?forumId="+thread.forumiddata+"&courseId="+courseId+"&threadId="+thread.threadId+"'>Move</a> <a href='modify-post?forumId="+thread.forumiddata+"&courseId="+courseId+"&threadId="+thread.threadId+"'>Modify</a><a href='#' name='tabs' data-var='"+thread.threadId+"' class='mark-remove'> Remove </a></td> ";
                            html += "<td>" + thread.replyby + "</td>";
                            html += "<td>" + thread.views + "</td>";
                            html += "<td>" + thread.postdate + "</td>";
                    }
                 }
                else {

                    html += "<tr> <td><a href='#'>" + (thread.subject) + "</a> " + thread.name + " </td> ";
                    html += "<td>" + thread.replyby + "</td>";
                    html += "<td>" + thread.views + "</td>";
                    html += "<td>" + thread.postdate + "</td>";
                }
            }
        });
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable();


    }
    else if (result.status == -1) {
        $('#forum-table').hide;
    }

    $("a[name=tabs]").on("click", function () {
        var threadsid = $(this).attr("data-var");
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
                    jQuerySubmit('mark-as-remove-ajax', {threadId:threadId}, 'markAsRemoveSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }

        });

    });
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

