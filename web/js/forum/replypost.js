$(document).ready(function ()
{    initEditor();
    $("#reply-btn").click(function()
    {
        document.getElementById("reply-btn").disabled = 'true';
        tinyMCE.triggerSave();
        var courseId = $(".course-id").val();
        var forumId = $(".forum-id").val();
        var threadId = $(".thread-id").val();
        var isPost = $(".isPost").val();
        var subject = $(".subject").val();
        var body = $("#post-reply").val();
        var parentId = $(".parent-id").val();
        var replyDetails = {isPost:isPost,courseId:courseId,forumId:forumId,threadId:threadId,subject:subject,body:body,parentId:parentId};
        jQuerySubmit('reply-post-ajax',replyDetails,'replyPost');
    });
});

function replyPost(response)
{
  response = JSON.parse(response);
    console.log(response);
    var courseId = $(".course-id").val();
    var forumId = $(".forum-id").val();
    var threadId = $(".thread-id").val();
    var isPost = response.data;
    if(response.status == 0)
    {
        document.getElementById("reply-btn").disabled = 'false';

        if(isPost)
        {
            window.location = "list-post-by-name?cid="+courseId+"&forumid="+forumId;
        }
        else
        {
            window.location = "post?courseid="+courseId+"&forumid="+forumId+"&threadid="+threadId;
        }
    }
}
