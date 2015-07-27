
$(document).ready(function () {
    var sessionCount = 0;
    $('.display-tutor-table').DataTable({bPaginate: false});
    var sessionVar = $.session.get("userNotFound");
    if(sessionVar)
    {
        if(sessionCount == 0)
        {
            $("#user-div").append("<b>Following usernames were not found :</b>&nbsp;");
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
    $('#check-none').click(function()
    {
        $('.list input[type = "checkbox"]').prop('checked', false);
    });
    $('#check-all').click(function()
    {
        $('.list input[type = "checkbox"]').prop('checked', true);
    });
};

function updateInfo()
{
    $("#update-button").click(function(){
        var cid = $(".courseId").val();
        var userNames = $("#tutor-text").val();
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
        var data =  {courseId:cid,username:userNames,checkedTutor: markArray,sectionArray:sectionArray};
        jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');
    });
}
function markUpdateSuccess(response){
    var result = JSON.parse(response);
    $.session.set("userNotFound", result.data.userNotFound);
    location.reload();
}
