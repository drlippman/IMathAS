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
        var forumid = $("#forum-id").val();
        var message = $("#message").val();
        jQuerySubmit('modify-post-ajax',{threadId: threadId ,forumid:forumid, subject: subject, message: message},'PostSuccess');
    });

});
function PostSuccess(response)
{

    var forumid = $("#forum-id").val();
    var courseid = $("#course-id").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forumid="+forumid;
    }
}