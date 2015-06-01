
$(document).ready(function ()
{
    var forumid= $('#forumid').val();
    jQuerySubmit('get-thread-ajax',{forumid: forumid },'threadSuccess');

});

function threadSuccess(response)
{console.log(response);
    var result = JSON.parse(response);
    var fid= $('#forumid').val();

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
                    html += "<tr> <td><a href='#'>" +(thread.subject) +"</a> "+ thread.name+" </td> ";
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

}
