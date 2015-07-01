$(document).ready(function ()
{    initEditor();
    $("#reply-btn").one('click',function()
    {
        document.getElementById("reply-btn").disabled = 'true';
        tinyMCE.triggerSave();
        var courseId = $(".course-id").val();
        var forumId = $(".forum-id").val();
        var threadId = $(".thread-id").val();
        var subject = $(".subject").val();
        var body = $("#post-reply").val();
        var parentId = $(".parent-id").val();
        var replyDetails = {courseId:courseId,forumId:forumId,threadId:threadId,subject:subject,body:body,parentId:parentId};
        jQuerySubmit('reply-post-ajax',replyDetails,'replyPost');
    });
});

function replyPost(response)
{
  response = JSON.parse(response);
    var courseId = $(".course-id").val();
    var forumId = $(".forum-id").val();
    var threadId = $(".thread-id").val();
    if(response.status == 0)
    {
        document.getElementById("reply-btn").disabled = 'false';
        window.location = "post?courseid="+courseId+"&forumid="+forumId+"&threadid="+threadId;
    }
}
