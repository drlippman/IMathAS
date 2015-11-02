
$(document).ready(function ()
{
    $('.select_option').val(-1);
    page = $('#page').val();
    forumid= $('#forumid').val();
    courseid = $("#courseid").val();
    change();
    select();
 });
 function select()
 {
     $('.select_option').click(function(){
         selected = $('.select_option :selected').val();
          if(selected == 0)
         {

             window.location = "list-post-by-name?page="+page+"&cid="+courseid+"&forumid="+forumid;
         }
         else if(selected == 1)
         {

             $('.forum-table').DataTable().destroy();
             window.location = "thread?page=-2&cid="+courseid+"&forum="+forumid;
         }
         else if(selected == 2)
         {
             $('.forum-table').DataTable().destroy();
             window.location = "thread?page=-1&cid="+courseid+"&forum="+forumid;
         }
         else if(selected == 3)
         {
             window.location = "thread?page=1&cid="+courseid+"&forum="+forumid;
         }
         else if(selected == 4)
         {
             window.location = "thread?cid="+courseid+"&forum="+forumid+"&markallread=true&page="+page;
         }
     });
 }
 function change()
 {
     $('#change-button').click(function(){
         var searchText = $('#search_text').val();

         if(searchText.length>0)
         {
             if(searchText.match(/^[a-z A-Z 0-9-]+$/))
             {
                 $('#flash-message').hide();
                 if(document.getElementById('searchAll').checked)
                 {
                     $('#searchpost').show();
                     $('#flash-message').hide();
                     $('#myForm').submit();
                 }
                 else
                 {
                     $('#searchpost').show();
                     $('#flash-message').hide();
                     $('#myForm').submit();
                 }
             }
             else
             {
                 $('#flash-message').show();
                 $('#flash-message').html("<div class='alert alert-danger'>Search text can contain only alphanumeric values");
                 $('#search_text').val(null);
             }
         }else
         {
             $('#flash-message').show();
             $('#flash-message').html("<div class='alert alert-danger'>Search text cannot be blank");
         }

     });
 }
function changeImage(element,checkFlagValue, rowId) {

    var userId = $("#user-id").val();

    var row = {rowId: rowId,userId:userId};
    jQuerySubmit('change-image-ajax', row,'flagResponse');

}


function flagResponse()
{
    window.location.reload();
}

function markAsRemoveSuccess(response) {
    var forumid = $("#forumid").val();
    var courseid = $("#courseid").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forum="+forumid;
    }
}

$("a[name=tabs]").on("click", function (event) {
    event.preventDefault();
    var threadsid = $(this).attr("data-var");
    var checkPostOrThread = 1;
    var html = '<div><p>Are you SURE you want to remove this thread and all replies?</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "confirm": function () {
                $(this).dialog("close");
                var threadId = threadsid;
                jQuerySubmit('mark-as-remove-ajax', {threadId:threadId,checkPostOrThread:checkPostOrThread}, 'markAsRemoveSuccess');
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
});

function chgtagfilter() {
    var tagfilter = document.getElementById("tagfilter").value;
    window.location = "thread?page=&cid="+courseid+"&forum=" + forumid+'&tagfilter='+tagfilter;

}
function chgfilter() {
    var ffilter = document.getElementById("ffilter").value;
    window.location = "thread?page=&cid="+courseid+"&forum=" + forumid+'&ffilter='+ffilter;
}