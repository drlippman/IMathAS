$(document).ready(function () {
  initEditor();
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
});


