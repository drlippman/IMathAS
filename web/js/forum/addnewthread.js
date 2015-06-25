$(document).ready(function () {
    initEditor();
    $("#addNewThread").one('click',function()
    {
        document.getElementById("addNewThread").disabled = 'true';

        tinyMCE.triggerSave();
            var forumId = $("#forumId").val();
            var subject = $(".subject").val();
        var date = $( "#datepicker-id input" ).val();
        var time = $("#w1").val();
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
            var threadDetails = {forumId:forumId,subject:subject,body:body,postType:postType,alwaysReplies:alwaysReplies,date : date ,time :time };
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
        document.getElementById("addNewThread").disabled = 'false';

        window.location = "thread?cid="+courseId+"&forumid="+forumId;
    }
}
