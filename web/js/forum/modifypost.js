$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });

    $("#save-changes").click(function()
    {

        tinyMCE.triggerSave();
        var subject = $(".subject").val();
        if(!subject.length > 0)
        {
            $('#flash-message').show();
            $(".subject").css('border-color', 'red');
            $('#flash-message').html("<div class='alert alert-danger'>Subject cannot be blank");
        }else{
        var threadId = $("#thread-id").val();
        var subject = $(".subject").val();
        var forumId = $("#forum-id").val();
        var message = $("#message").val();
        var replayBy = $("#reply-by").val();
            var postType = $("#post-type").val();
        jQuerySubmit('modify-post-ajax',{threadId: threadId ,forumid:forumId, subject: subject, message: message,replayBy:replayBy,postType:postType},'PostSuccess');
        }
    });
    $("input").keypress(function(){
        $(".subject").css('border-color', '');
        $('#flash-message').hide();
    });

});
function PostSuccess(response)
{

    var forumId = $("#forum-id").val();
    var courseId = $("#course-id").val();
    response = JSON.parse(response);
    if(response.status == 0)
    {
        window.location = "thread?cid="+courseId+"&forumid="+forumId;
    }
}