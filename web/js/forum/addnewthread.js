$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
//    $("#addNewThread").click(function()
//    {
//        tinyMCE.triggerSave();
//        var forumId = $("#forumId").val();
//        var subject = $(".subject").val();
//        var courseId =$("#courseId").val();
//        if(!subject.length > 0)
//        {
//            $('#flash-message').show();
//            $(".subject").css('border-color', 'red');
//            $('#flash-message').html("<div class='alert alert-danger'>Subject cannot be blank");
//        }
//        else
//        {
//            document.forms["add-thread"].submit();
//        }
//    });

//    $("input").keyup(function(e){
//        if(e.keyCode == 8 || e.keyCode == 46)
//        {
//            $(".subject").css('border-color', '');
//            $('#flash-message').hide();
//        }
//    });
    var i=1;
    $('.add-more').click(function(e){
        e.preventDefault();
        $(this).before('<input required="" name="file-'+i+'" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-'+i+'placeholder="Description"><br>');
        i++;
    });
});


