$(document).ready(function () {
    manageQuestionSelectedCheckbox();
    $('input[name = "manage-question-header-checked"]:checked').prop('checked', false);
});
function manageQuestionSelectedCheckbox() {
    $('.manage-question-table input[name = "manage-question-header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#manage-question-set-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#manage-question-set-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}