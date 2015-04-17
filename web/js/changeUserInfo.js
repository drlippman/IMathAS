function checkConfirmPassword(){
    $("#pwd").change(function(){

        if($("#pwd:checked").val()){
            $(".change-password-content").show();
        }else{
            $(".change-password-content").hide();
        }
    });
}

$(document).ready(function(){

    $(".change-password-content").hide();
    checkConfirmPassword();

});