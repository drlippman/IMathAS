$(document).ready(function ()
{
    initEditor();
    $(".reply-btn").one('click',function()
    {
        tinyMCE.triggerSave();
        var courseId = $(".course-id").val();
        var forumId = $(".forum-id").val();
        var threadId = $(".thread-id").val();
        var subject = $("#sub").val();
        var parentId =$(".parent").val();
        var body = $("#post-reply").val();
        var replyDetails = {courseId : courseId,parentId : parentId,forumId : forumId,threadId : threadId,subject : subject,body : body};
        jQuerySubmit('reply-list-post-ajax',replyDetails,'replyPostSuccess');
    });

});

function replyPostSuccess(response)
{
    console.log(response);
    response = JSON.parse(response);
    var courseId = $(".course-id").val();
    var forumId = $(".forum-id").val();
    var threadId = $(".thread-id").val();
    if(response.status == 0)
    {
        window.location = "list-post-by-name?cid="+courseId+"&forumid="+forumId;
    }
}
