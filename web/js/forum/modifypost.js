$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    $("input").keypress(function(e){
        var subject = $(".subject").val();
        $(".subject").css('border-color', '');
        $('#flash-message').hide();
        if(subject.length > 45)
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

    $("input").keyup(function(e){
        if(e.keyCode == 8 || e.keyCode == 46)
        {
            $(".subject").css('border-color', '');
            $('#flash-message').hide();
        }
    });
    var i = 1;
    $('.add-more').click(function(e){
        e.preventDefault();
        $(this).before('<input name="file-'+i+'" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-'+i+'" placeholder="Description"><br>');
        i++;
    });
});




