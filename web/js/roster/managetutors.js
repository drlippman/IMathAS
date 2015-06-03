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
        //var data =  {courseid:cid};
        var usernames = $("#tutor-text").val();
        //var data =  {courseid:cid,username:usernames};
        //jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');

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
function markUpdateSuccess(response){console.log(response);
    var result = JSON.parse(response);
    $.session.set("userNotFound", result.userNotFound);
    location.reload();
}
