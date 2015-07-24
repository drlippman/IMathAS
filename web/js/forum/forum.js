var selected;
$('.search-dropdown').click(function(){
    selected = $('.select_option :selected').val();
});
$(document).ready(function ()
{
    var courseId = $('.courseId').val();
    jQuerySubmit('get-forums-ajax', {cid: courseId}, 'forumsSuccess');
    $('#search-thread').hide();
    $('#search-post').hide();
    $('#result').hide();


    $('#forum_search').click(function ()
    {

        var searchReg = /^[a-zA-Z0-9-]+$/;
        var search = $('#search_text').val();
        var courseId = $('.courseId').val();
        if(search.length > 0)
        {
            if(search.match(/^[a-z A-Z 0-9-]+$/))
            {

                $('#flash-message').hide();
                if(selected == 0)
                {
                    $('#search-thread').show();
                    $('#display').hide();
                    $('#search-post').hide();
                    $('#result').hide();
                    jQuerySubmit('get-forum-name-ajax',{search: search, cid: courseId , value: selected},'threadSuccess');
                }
                else
                {
                    $('#search-thread').hide();
                    $('#display').hide();
                    $('#search-post').show();
                    $('#result').hide();
                    jQuerySubmit('get-search-post-ajax',{search: search, courseid: courseId , value: selected},'postSuccess');

                }

            }
            else
            {
                $('#flash-message').show();
                $('#flash-message').html("<div class='alert alert-danger'>Search text can contain only alphanumeric values");
            }

        }
        else
        {
            $('#flash-message').show();
            $('#flash-message').html("<div class='alert alert-danger'>Search text cannot be blank");


        }



    });

});

function postSuccess(response)
{
    var courseId = $('.courseId').val();
    response = JSON.parse(response);

    if (response.status == 0)
    {
        var html =" ";
        $('#search-post').empty();
        var postData = response.data.data;
        $.each(postData, function(index, Data)
        {
               var result = Data.message.replace(/<[\/]{0,1}(p)[^><]*>/ig,"");
                html = "<div class='block'>";
                html += "<b><label  class='subject'>"+Data.subject+"</label></b>";
                html += "&nbsp;&nbsp;&nbsp;in(&nbsp;<label class='forumname'>"+Data.forumName+"</label>)";
                html += "<br/>Posted by:&nbsp;&nbsp;<label class='postedby'>"+Data.name+"</label>";
                html += "&nbsp;&nbsp;<label id='postdate'>"+Data.postdate+"</label>";
                html += "</div><div class=blockitems>";
                html += "<label id='message'>"+result+"</label>";
                html += "<p><a href='post?courseid=" + courseId + "&threadid=" + Data.threadId +"&forumid="+ Data.forumiddata+"'</a>Show full thread</p>";
                html += "</div>\n";
                $('#search-post').append(html);
        });
    }
    else
    {
        $('#search-post').hide();
        $('#result').show();
    }
}

function threadSuccess(response)
{
    var courseId = $('.courseId').val();
    response = JSON.parse(response);

    if (response.status == 0)
    {
        var searchdata = response.data;
        var html = "";
        $.each(searchdata, function(index, search)
        {
            html += "<tr> <td><a href='post?courseid=" + courseId + "&threadid=" + search.threadId +"&forumid="+ search.forumIdData+"'>" +(search.subject) +"</a> "+ search.name+" </td> ";
            html += "<td>" + search.replyBy + "</td>";
            html += "<td>" + search.views + "</td>";
            html += "<td>" + search.postdate + "</td>";
       });
      $(".forum-search-table-body tr").remove();
      $(".forum-search-table-body").append(html);
      $('.forum-search-table').DataTable({bPaginate:false});
    }
    else
    {
        $('#result').show();
        $('#search-thread').hide();
    }

}
function forumsSuccess(response) {

    response = JSON.parse(response);
  
    if (response.status == 0)
    {
        var forums = response.data;

    }
    showForumTable(forums);
}

    function showForumTable(forums)
    {
        var courseId = $('.courseId').val();
        var html = "";
        $.each(forums, function (index, forum)
        {
            if(forum.rights > 10)
            {
                html += "<tr><td>&nbsp;&nbsp;<a href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>" + forum.forumName + "</a></td>+ <a href='Modify'> ";
                html += "<td>&nbsp;&nbsp;<a>Modify</a></td>";
                html += "<td>&nbsp;&nbsp;&nbsp;" + forum.threads + "</td>";
                html += "<td>&nbsp;&nbsp;&nbsp;" + forum.posts + "</td>";
                html += "<td>&nbsp;&nbsp;&nbsp;" + forum.lastPostDate + "</td>";
            }
            else if(forum.endDate > forum.currentTime )
            {
                html += "<tr><td>&nbsp;&nbsp;<a href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>" + forum.forumName + "</a></td>+ <a href='Modify'> ";
                html += "<td>&nbsp;&nbsp;<a>Modify</a></td>";
                html += "<td>&nbsp;&nbsp;" + forum.threads + "</td>";
                html += "<td>&nbsp;&nbsp;" + forum.posts + "</td>";
                html += "<td>&nbsp;&nbsp;" + forum.lastPostDate + "</td>";
            }

        });
        $(".forum-table-body tr").remove();
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable({"ordering": false,bPaginate:false});
    }

