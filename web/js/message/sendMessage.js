
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

        if(receiver == 0 && subject == '')
        {
            $('#subject-error').html('Please fill out subject field');
            $('#receiver').html('Please fill out receiver field');
        }
        if(receiver != 0)
        {
            if(subject == '')
            {
                $('#subject').css('border-color','red');
                $('#subject-error').html('Please fill out subject field');
            } else if(subject != '')
            {
                $('.subject-error-message').removeClass("subject-error-message");
                jQuerySubmit('confirm-message',{cid: cid , receiver: receiver, subject: subject, body: body},'sendMessage');
            }
        }
        else
        {
            $('#seluid').css('border-color','red');
            $('#receiver').html('Please fill out receiver field');
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


function changeSubject()
{
    var subject = $("#subject").val();
    var receiver = $("#seluid").val();
    if(subject != '' && receiver != '')
    {
        $('#subject-error').html('');
        $('#receiver').html('');
    }else if(subject != '')
    {
        $('#subject-error').html('');
    }else if(receiver != '')
    {
        $('#receiver').html('');
    }
}

function sendMessage(response)
{
        var cid = $(".send-msg").val();
        var result = JSON.parse(response);

        if(result.status == 0)
        {

            window.location = "index?cid="+cid;
        }
}

function clickAndDisable(link) {

    // add any additional logic here

    // disable subsequent clicks
    link.onclick = function(event) {
        e.preventDefault();
    }
}
