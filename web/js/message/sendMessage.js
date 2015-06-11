
$(document).ready(function () {

    $('#subjecttext').hide();
    $('#to').hide();
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
                $('#subjecttext').hide();
                $('#to').hide();
                jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
        }else{
//            var msg="Subject field cannot be blank";
//            CommonPopUp(msg);
            $('#subjecttext').show();
            $('#to').hide();

        }
    }else{
//        var msg="Please select atleast one user";
//        CommonPopUp(msg);
        $('#to').show();
        $('#subjecttext').hide();
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