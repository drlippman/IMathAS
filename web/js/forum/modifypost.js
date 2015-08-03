$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
//    $("input").keypress(function(e){
//        var subject = $(".subject").val();
//        $(".subject").css('border-color', '');
//        $('#flash-message').hide();
//        if(subject.length > 45)
//        {
//            $('#flash-message').show();
//            $(".subject").css('border-color', 'red');
//            $('#flash-message').html("<div class='alert alert-danger'>The Subject field cannot contain more than 60 characters!");
//            return false;
//        }else{
//            $(".subject").css('border-color', '');
//            $('#flash-message').hide();
//        }
//
//    });
});
var filecnt = 1;
function addnewfile(t) {
    var s = document.createElement("span");
    s.innerHTML ="Description:<br/><input type='text' size=0 style='width: 30%;height: 30px; border: #6d6d6d 1px solid;' name='newfiledesc-\"+filecnt+\"' value='' class='subject'><br/>File: <input type='file' name='newfile-\"+filecnt+\"' /><br/>";
    t.parentNode.insertBefore(s,t);
    filecnt++;
}



