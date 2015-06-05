
$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });

});


function getEmailData(){
    $("#email-button").click(function()
    {
        tinyMCE.triggerSave();
        var cid = $(".send-msg").val();
        var subject = $(".subject").val();
        var body = $("#message").val();
        var whomToSend = $("#send-to").val();
        var assessmentName = $("#roster-assessment-data").val();
        var allData = {cid: cid, subject: subject, body: body,whomToSend: whomToSend, assessmentName: assessmentName }
        alert(allData);
        if(receiver != 0){
            if(subject != ''){
                if (body != ''){
                    jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
                }else{
                    alert('Body field cannot be blank');
                }

            }else{
                alert('Subject field cannot be blank');
            }
        }else{
            alert('User is not selected');
        }
    });
}