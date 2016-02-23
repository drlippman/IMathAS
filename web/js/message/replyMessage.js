$(document).ready(function () {
    initEditor();

    $("#msg-btn").click(function () {
        tinyMCE.triggerSave();
        var cid = $(".send-msg").val();
        var receiver = $(".msg-sender").val().trim();
        var sender = $(".msg-receiver").val().trim();
        var body = $("#message").val().trim();
        var parentId = $(".parent-id").val();
        var checkedValue = $('.header-checked:checked').val();
        var baseId = $(".base-id").val();
        var isReplied = $(".is-replied").val();
        var subject = $(".subject").val().trim();
        if(subject == '')
        {
            $('#subject-error').html('Please fill out subject field');
        }else{
            var messageDetails = {cid: cid, sender: sender, receiver: receiver, subject: subject, body: body, parentId: parentId, baseId: baseId, isReplied: isReplied,checkedValue :checkedValue };
            jQuerySubmit('reply-message-ajax', messageDetails, 'replyMessage');
        }
    });

});

function replyMessage(response) {
    var cid = $(".send-msg").val();
    var result = JSON.parse(response);
    if (result.status == 0) {
        window.location = "index?cid=" + cid;
    }
}

function changeSubject()
{
    var subject = $("#subject").val();

    if(subject != '')
    {
        $('#subject-error').html('');
    }
}
