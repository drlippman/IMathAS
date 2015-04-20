$(document).ready(function(){
    checkConfirmPassword();
});

function checkConfirmPassword(){
    $("#pwd").change(function(){
        if($("#pwd:checked").val()){
            $(".change-password-content").show();
        }else{
            $(".change-password-content").hide();
        }
    });

    if($("#pwd:checked").val() == undefined)
    {
        $(".change-password-content").hide();
    }
    else{
        $(".change-password-content").show();
    }
}