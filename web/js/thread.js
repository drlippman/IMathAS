
$(document).ready(function ()
{
    var forumid= $('#forumid').val();
    jQuerySubmit('get-thread-ajax',{forumid: forumid },'threadSuccess');

});

function threadSuccess(response)
{
    var result = JSON.parse(response);
    var fid= $('#forumid').val();
    var courseId= $('#course-id').val();
    if (result.status == 0)
    {

        var threads = result.threadData;
        var html = "";
        $.each(threads, function(index, thread)
        {

            if(fid == thread.forumiddata){
                if(thread.replyby == null)
                {

                    thread.replyby= 0;
                    //html += "<tr> <td><a href='#'>" +(thread.subject) +"</a> "+ thread.name+" </td> ";
                    html += "<tr> <td><a href='post?threadid=9'>" +(thread.subject) +"</a> "+ thread.name+" <a href='move-thread?forumId=2&courseId=2&threadId=7'>Move</a> <a href='modify-post?forumId=1&courseId=2&threadId=5'>Modify</a><a href='#'> Remove </a></td> ";
                    html += "<td>" + thread.replyby + "</td>";
                    html += "<td>" + thread.views + "</td>";
                    html += "<td>" + thread.postdate + "</td>";
                }
                else
                {

                    html += "<tr> <td><a href='#'>" +(thread.subject) +"</a> "+ thread.name+" </td> ";
                    html += "<td>" + thread.replyby + "</td>";
                    html += "<td>" + thread.views + "</td>";
                    html += "<td>" + thread.postdate + "</td>";
                }
            }
        });
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable();



    }
    else if(result.status == -1)
    {
        $('#forum-table').hide;
    }

}
