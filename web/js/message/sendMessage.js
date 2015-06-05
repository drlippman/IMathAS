
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
    if(receiver != 0){
        if(subject != ''){
            if (body != ''){
                jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
            }else{

            }

        }else{
            alert('Subject field cannot be blank');
        }
    }else{
        alert('User is not selected');
    }
    });
});

function sendMessage(response)
{
        var cid = $(".send-msg").val();
        console.log(response);
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            alert('Message sent successfully')
            window.location = "index?cid="+cid;
        }
}