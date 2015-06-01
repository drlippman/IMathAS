function markCheck()
{
    $('#checkNone').click(function()
    {
        $('.list input[type = "checkbox"]').prop('checked', false);
    });
    $('#checkAll').click(function()
    {
        $('.list input[type = "checkbox"]').prop('checked', true);
    });
};

function updateInfo()
{
    $("#update-btn").click(function(){
        var cid = $(".courseId").val();
        var data =  {courseid:cid};
        var usernames = $("#tutor-text").val();
        var data =  {courseid:cid,username:usernames};
        jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');

        var markArray = [];
        $('.tutor-table-body input[name = "tutor-check"]:checked').each(function() {
            markArray.push($(this).val());
            $(this).closest('tr').css('font-weight', 'normal');
            $(this).prop('checked',false);
        });

        $('.tutor-table-body select[name = "select-section"]:selected').each(function(){
            $('#user-sent-id').on('change', function() {

            });
        });

        var data =  {courseid:cid,username:usernames,checkedtutor: markArray};
        jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');
    });
}
function markUpdateSuccess(response){
    var result = JSON.parse(response);
    $.session.set("userNotFound", result.userNotFound);
    location.reload();
}
