$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    $("#addthread").click(function()
    {

        tinyMCE.triggerSave();
            var forumId = $("#forumId").val();
            var subject = $(".subject").val();
            var body = $("#message").val();
            var threadDetails = {forumId:forumId,subject:subject,body:body};
            jQuerySubmit('add-new-thread-ajax',threadDetails,'newThreadSuccess');
    });


});
function newThreadSuccess(response)
{
    console.log(JSON.parse(response));
    var forumId = $("#forumId").val();
    var courseId = $('#courseId').val();
    response = JSON.parse(response);
    if (response.status == 0)
    {

        window.location = "thread?cid="+courseId+"&forumid="+forumId;
    }
}
