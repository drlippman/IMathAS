$(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });
    validateForm();
});
function validateForm(){
    $('#message-button').click(function() {
        tinyMCE.triggerSave();
        var messageBody =$("#message").val();
        var subjectBody = document.forms["myForm"]["subject"].value;
        if (subjectBody == null || subjectBody == "") {
            var msg = "Subject cannot be blank.";
            CommonPopUp(msg);
            return false;
        }
        else if (messageBody == null || messageBody == "") {
            var msg = "Message body cannot be blank.";
            CommonPopUp(msg);
            return false;
        }
    });
}
