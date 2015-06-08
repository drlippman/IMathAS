
$(document).ready(function () {
    var sessionCount = 0;
    $('.display-tutor-table').DataTable();
    var sessionVar = $.session.get("userNotFound");
    if(sessionVar)
    {
        if(sessionCount == 0)
        {
            $("#user-div").append("<b>Following Usernames Were Not Found :</b>&nbsp;");
            sessionCount++;
        }
            $("#user-div").append(sessionVar);
    }
    $.session.clear();
    markCheck();
    updateInfo();
});
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
        var usernames = $("#tutor-text").val();
        var markArray = [];
        $('.tutor-table-body input[name = "tutor-check"]:checked').each(function() {
            markArray.push($(this).val());
            $(this).prop('checked',false);
        });
        var sectionArray = [];
         $('.tutor-table-body select[name = "select-section"]').each(function()
         {
             var tempArray = {tutorId:this.id,tutorSection:this.value};
             sectionArray.push(tempArray);

         });
        var data =  {courseid:cid,username:usernames,checkedtutor: markArray,sectionArray:sectionArray};
        jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');
    });
}
function markUpdateSuccess(response){
    var result = JSON.parse(response);
    $.session.set("userNotFound", result.data.userNotFound);
    location.reload();
}
