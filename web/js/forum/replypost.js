$(document).ready(function ()
{
        initEditor();
    $("#reply-btn").one('click',function()
    {
        document.getElementById("reply-btn").disabled = 'true';

        tinyMCE.triggerSave();
        var courseid = $(".courseid").val();
        var forumid = $(".forumid").val();
        var threadid = $(".threadid").val();
        var subject = $(".subject").val();
        var body = $("#postreply").val();
        var parentId = $(".parent-id").val();
        var replyDetails = {couserid:courseid,forumid:forumid,threadid:threadid,subject:subject,body:body,parentId:parentId};
        jQuerySubmit('reply-post-ajax',replyDetails,'replyPost');
    });

});

function replyPost(response)
{
  response = JSON.parse(response);
    var courseId = $(".courseid").val();
    var forumId = $(".forumid").val();
    var threadId = $(".threadid").val();
    if(response.status == 0)
    {
        document.getElementById("reply-btn").disabled = 'false';
        window.location = "post?courseid="+courseId+"&forumid="+forumId+"&threadid="+threadId;
    }
}
