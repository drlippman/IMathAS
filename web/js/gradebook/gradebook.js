$(document).ready(function () {
    var courseId = $(".course-info").val();
    var userId = $(".user-info").val();
    var allMessage = {courseId: courseId, userId:userId};
    jQuerySubmit('display-gradebook-ajax', allMessage, 'showGradebookSuccess');
    selectCheckBox();
});

function showGradebookSuccess(response){
console.log(response);
}
function selectCheckBox() {
    $('.check-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', true);
        })
    });

    $('.uncheck-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', false);
        })
    });
}

