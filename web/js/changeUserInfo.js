$(document).ready(function(){
    checkConfirmPassword();

});

function checkConfirmPassword()
{
    $("#pwd").change(function(){
        if($("#pwd:checked").val()){
            $(".toggle-password").slideDown();
        }else{
            $(".toggle-password").slideUp();
        }
    });

    if($("#pwd:checked").val() == undefined)
    {
        $(".toggle-password").hide();
    }
    else{
        $(".toggle-password").show();
    }
}