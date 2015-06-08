$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    $("#addthread").click(function()
    {

        tinyMCE.triggerSave();
        alert("Hiiiii");
//            var courseid = $(".courseid").val();
//            var forumid = $(".forumid").val();
//            var threadid = $(".threadid").val();
//            var subject = $(".subject").val();
//            var body = $("#postreply").val();
//            var replyDetails = {couserid:courseid,forumid:forumid,threadid:threadid,subject:subject,body:body};
//            jQuerySubmit('reply-post-ajax',replyDetails,'replyPost');
    });


});
