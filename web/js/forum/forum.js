var selected;
$('.search-dropdown').click(function(){
    selected = $('.select_option :selected').val();
    $(".select_option").css('border-color', 'white');
});
$(document).ready(function ()
{  var courseId = $('.courseId').val();
    var newPost = $('#new-post').val();
    jQuerySubmit('get-forums-ajax', {cid: courseId,newPost:newPost}, 'forumsSuccess');
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
            $("#search_text").css('border-color', 'white');
            if(search.match(/^[a-z A-Z 0-9-]+$/))
            {
                $('#flash-message').hide();
                if(selected == 0)
                {
                    $('#search-thread').show();
                    $('#display').hide();
                    $('#search-post').hide();
                    $('#result').hide();
                    jQuerySubmit('get-forum-name-ajax',{search: search, courseId: courseId , value: selected},'threadSuccess');
                }
                else if(selected == 1)
                {
                    $('#search-thread').hide();
                    $('#display').hide();
                    $('#search-post').show();
                    $('#result').hide();
                    jQuerySubmit('get-search-post-ajax',{search: search, courseid: courseId , value: selected},'postSuccess');
                }else
                {
                    $(".select_option").css('border-color', 'red');
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
            $("#search_text").css('border-color', 'red');
            if(selected != 1 && selected != 0)
            {

                $(".select_option").css('border-color', 'red');
            }
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
            if(search.parent ==0){


            html += "<tr> <td><a href='post?courseid=" + courseId + "&threadid=" + search.threadId +"&forumid="+ search.forumIdData+"'>" +(search.subject) +"</a> "+ search.name+" </td> ";
            html += "<td>" + search.replyBy + "</td>";
            html += "<td>" + search.views + "</td>";
            html += "<td>" + search.postdate + "</td>";
            }
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
                if(forum.countId == 0)
                {
                    html += "<tr><td ><a  href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>"+capitalizeFirstLetter(forum.forumName)+ "</a></td>+ <a href='Modify'> ";
                }else{
                    html += "<tr><td ><a  href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>" +capitalizeFirstLetter(forum.forumName) +"</a><br>&nbsp;<a href='thread?cid="+courseId+"&forumid="+forum.forumId+"&page=1'<span class='new-post'> Post("+forum.count+")</span></a>";
                }

                html += "<td><a href='add-forum?id="+forum.forumId+"&cid="+courseId+"&fromForum=1'>Modify</a></td>";
                html += "<td>" + forum.threads + "</td>";
                html += "<td>" + forum.posts + "</td>";
                html += "<td>" + forum.lastPostDate + "</td>";
            }
            else if(forum.avail == 2 || forum.avail == 1 && forum.startDate < forum.currentTime && forum.endDate > forum.currentTime )
            {
                    if(forum.countId == 0)
                    {
                        html += "<tr><td><a  href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>"+capitalizeFirstLetter(forum.forumName)+ "</a></td>+ <a href='Modify'> ";
                    }else{
                        html += "<tr><td><a  href='thread?cid="+courseId+"&forumid="+forum.forumId+"'>" +capitalizeFirstLetter(forum.forumName) +"</a><br>&nbsp;<a href='thread?cid="+courseId+"&forumid="+forum.forumId+"&page=1'<span class='new-post'> Post("+forum.count+")</span></a>";
                    }
//                    html += "<td>&nbsp;&nbsp;<a href='add-forum?id="+forum.forumId+"&cid="+courseId+"&fromForum=1'>Modify</a></td>";
                    html += "<td>" + forum.threads + "</td>";
                    html += "<td>" + forum.posts + "</td>";
                    html += "<td>" + forum.lastPostDate + "</td>";
            }
        });
        $(".forum-table-body tr").remove();
        $(".forum-table-body").append(html);
        $('.forum-table').DataTable({"ordering": false,bPaginate:false});
    }

