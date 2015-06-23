$(document).ready(function () {
    $('.student-data').DataTable();
});
function appendPrependReplaceText(value)
{
    var feedback_txt =  document.getElementById("feedback_txt").value;
    //alert(feedback_txt);
if(value == 1){
        $( ".feedback-text-id" ).each(function() {
            var feedback = $(this).val();
            $(this).val(feedback + feedback_txt);
        });

    }else if(value == 2){
        $( ".feedback-text-id" ).each(function() {
            var feedback = $(this).val();
            //alert(feedback_txt);
            //if(feedback_txt.length == 0 && feedbackValue != 1){
            //    var html = '<div><p>Are you sure? Its clear all feedbacks </p></div>';
            //    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            //        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            //        width: 'auto', resizable: false,
            //        closeText: "hide",
            //        buttons: {
            //            "Cancel": function () {
            //                $(this).dialog('destroy').remove();
            //                return false;
            //            },
            //            "confirm": function () {
            //                $(this).dialog("close");
            //                feedbackValue = 1;
            //                $(this).val(feedback_txt);
            //                return true;
            //            }
            //        },
            //        close: function (event, ui) {
            //            $(this).remove();
            //        }
            //    });
            //}else if(feedbackValue == 1){
            //$(this).val(feedback_txt);
            //}else{
                $(this).val(feedback_txt);
            //}

        });
    }else if(value == 3){
        $(".feedback-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback_txt + feedback );
        });
    }
}