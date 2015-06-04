$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });

    $("#save-changes").click(function()
    {

        tinyMCE.triggerSave();
        var threadId = $("#thread-id").val();
        var subject = $(".subject").val();
        var forumId = $("#forum-id").val();
        var message = $("#message").val();
        jQuerySubmit('modify-post-ajax',{threadId: threadId ,forumid:forumId, subject: subject, message: message},'PostSuccess');
    });

});
function PostSuccess(response)
{

    var forumId = $("#forum-id").val();
    var courseId = $("#course-id").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseId+"&forumid="+forumId;
    }
}