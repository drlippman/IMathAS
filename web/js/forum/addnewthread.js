$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    $("#addNewThread").click(function()
    {
        tinyMCE.triggerSave();
            var forumId = $("#forumId").val();
            var subject = $(".subject").val();
            if(!subject.length > 0)
            {
                $('#flash-message').show();
                $(".subject").css('border-color', 'red');
                $('#flash-message').html("<div class='alert alert-danger'>Subject cannot be blank");
            }
            else
            {
                document.getElementById("addNewThread").disabled = 'true';
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
                var settings = 0;
                var status = $('#post-anonymously').is(':checked');
                if(status == true){
                    settings = 1;
                }
                var threadDetails = {forumId:forumId,subject:subject,body:body,postType:postType,alwaysReplies:alwaysReplies,date : date ,time :time,settings:settings };
                jQuerySubmit('add-new-thread-ajax',threadDetails,'newThreadSuccess');
            }
    });
    $("input").keypress(function(e){
        var subject = $(".subject").val();
        $(".subject").css('border-color', '');
        $('#flash-message').hide();
        if(subject.length > 60)
        {
            $('#flash-message').show();
            $(".subject").css('border-color', 'red');
            $('#flash-message').html("<div class='alert alert-danger'>The Subject field cannot contain more than 60 characters!");
            return false;
        }else{
            $(".subject").css('border-color', '');
            $('#flash-message').hide();
        }

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
