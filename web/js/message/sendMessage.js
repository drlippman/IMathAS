
$(document).ready(function () {
    initEditor();
    $("#mess").click(function()
    {
        tinyMCE.triggerSave();
        var cid = $(".send-msg").val();
        var newTo = $("#newTo").val();
        if(newTo)
        {
            var receiver = $("#newUserId").val();
        }else
        {
            var receiver = $("#seluid").val();
        }
        var subject = $(".subject").val();
        var body = $("#message").val();
        if(receiver != 0)
        {
            if(subject != '')
            {
                        $('#subjecttext').hide();
                        $('#to').hide();
                        jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
            }
            else
            {
                    changeColorSub();
                    $('#to').css('color','white');


            }
        }
        else
        {
            changeColorTo();
            $('#subjecttext').css('color','white');
        }
    });
});

function sendMessage(response)
{
        var cid = $(".send-msg").val();
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            window.location = "index?cid="+cid;
        }
}
function changeColorTo(){

    $('#to').css('color','red');
}
function changeColorSub(){

    $('#subjecttext').css('color','red');
}
