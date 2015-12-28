
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
            if(subject == '')
            {
                $('#subject').css('border-color','red');
                alert('Please fill out subject field');
            } else if(subject != '')
            {
                    jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
            }
        }
        else
        {
            $('#seluid').css('border-color','red');
            alert('Please fill out receiver field')
        }
    });

    $('#seluid').click(function()
    {
        $('#seluid').css('border-color','grey');
    });

    $('#subject').click(function()
    {
        $('#subject').css('border-color','grey');
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
