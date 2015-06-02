
$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });

$("#mess").click(function()
{
    tinyMCE.triggerSave();
    var cid = $(".send-msg").val();
    var receiver = $("#seluid").val();
    var subject = $(".subject").val();
    var body = $("#message").val();
    jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
    });
});

function sendMessage(response)
{
        var cid = $(".send-msg").val();
        console.log(response);
        var result = JSON.parse(response);
        if(result.status == 0)
        {
        window.location = "index?cid="+cid;
        }
}