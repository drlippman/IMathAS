$(document).ready(function ()
{
    var courseId = $('.courseId').val();
    jQuerySubmit('get-forums-ajax', {cid: courseId}, 'forumsSuccess');
    $('#searchthread').hide();
    $('#searchpost').hide();
    $('#result').hide();

    $('#forum_search').click(function ()
    {

        var searchReg = /^[a-zA-Z0-9-]+$/;
        var search = $('#search_text').val();
        var courseId = $('.courseId').val();
        var val=document.querySelector('input[name="ForumForm[thread]"]:checked').value;


        if(search.length>0)
        {
            if(search.match(/^[a-z A-Z 0-9-]+$/))
            {
                ;
                $('#flash-message').hide();
                if(val == 'subject')
                {
                    $('#searchthread').show();
                    $('#display').hide();
                    $('#searchpost').hide();
                    $('#result').hide();
                    jQuerySubmit('get-forum-name-ajax',{search: search, cid: courseId , value: val},'threadSuccess');
                }
                else
                {
                    $('#searchthread').hide();
                    $('#display').hide();
                    $('#searchpost').show();
                    $('#result').hide();
                    jQuerySubmit('get-search-post-ajax',{search: search, cid: courseId , value: val},'postSuccess');

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
    response = JSON.parse(response);
    if (response.status == 0)
    {
        var postData = response.data.data;
        $.each(postData, function(index, Data)
        {
            var result = Data.message.replace(/<[\/]{0,1}(p)[^><]*>/ig,"");
            var html = "<div class='block'>";
            html += "<b><label  class='subject'>"+Data.subject+"</label></b>";
            html += "&nbsp;&nbsp;&nbsp;in(&nbsp;<label class='forumname'>"+Data.forumname+"</label>)";
            html += "<br/>Posted by:&nbsp;&nbsp;<label class='postedby'>"+Data.name+"</label>";
            html += "&nbsp;&nbsp;<label id='postdate'>"+Data.postdate+"</label>";
            html += "</div><div class=blockitems>";
            html += "<label id='message'>"+result+"</label>";
            html += "<p><a href='#'</a>Show full thread</p>";
            html += "</div>\n";
            $('#searchpost').append(html);
        });
    }
    else
    {
        $('#searchpost').hide();
        $('#result').show();
    }
}

function threadSuccess(response)
{

    response = JSON.parse(response);
    if (response.status == 0)
    {
        var searchdata = response.data;
        var html = "";
        $.each(searchdata, function(index, search)
        {
            html += "<tr> <td><a href='#'>" +(search.subject) +"</a> "+ search.name+" </td> ";
            html += "<td>" + search.replyby + "</td>";
            html += "<td>" + search.views + "</td>";
            html += "<td>" + search.postdate + "</td>";
       });
      $(".forumsearch-table-body tr").remove();
      $(".forumsearch-table-body").append(html);
      $('.forumsearch-table').DataTable();
    }
    else
    {
        $('#result').show();
        $('#searchthread').hide();
    }

}
function forumsSuccess(response) {

    response = JSON.parse(response);
    console.log(response);
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
//          alert(JSON.stringify(forum.enddate));
//            alert(JSON.stringify(forum.currenttime));

            if(forum.enddate > forum.currenttime)
            {
                html += "<tr> <td><a href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>" + capitalizeFirstLetter(forum.forumname) + "</a></td>+ <a href='Modify'> ";
                html += "<td>" + forum.threads + "</td>";
                html += "<td>" + forum.posts + "</td>";
                html += "<td>" + forum.lastPostDate + "</td>";
            }

        });
        $(".forum-table-body tr").remove();
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable();

    }

//function searchByForum(forumData, queryData)
//{
//    var filteredArray = [];
//    $.each(queryData, function (index, queryresult) {
//        $.each(forumData, function (index, forumresult) {
//            if (queryresult.id == forumresult.forumId)
//            {
//                filteredArray.push(forumresult);
//            }
//        });
//    });
//    showForumTable(filteredArray);
//
//}
