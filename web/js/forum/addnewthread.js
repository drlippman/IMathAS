$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    $("#addNewThread").click(function()
    {

        tinyMCE.triggerSave();
            var forumId = $("#forumId").val();
            var subject = $(".subject").val();

        var postType = "";
        var selected = $("#post-type-radio-list input[type='radio']:checked");
        if (selected.length > 0) {
            postType = selected.val();
        }
        var alwaysReplies = "";
        var selected = $("#always-replies-radio-list input[type='radio']:checked");
        if (selected.length > 0) {
            alwaysReplies = selected.val();
        }
            var body = $("#message").val();
            var threadDetails = {forumId:forumId,subject:subject,body:body,postType:postType,alwaysReplies:alwaysReplies};
            jQuerySubmit('add-new-thread-ajax',threadDetails,'newThreadSuccess');
    });


});
function newThreadSuccess(response)
{
    var forumId = $("#forumId").val();
    var courseId = $('#courseId').val();
    response = JSON.parse(response);
    if (response.status == 0)
    {

        window.location = "thread?cid="+courseId+"&forumid="+forumId;
    }
}
