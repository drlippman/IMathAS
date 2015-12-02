$(document).ready(function(){
    var isCourseHidden = $('.hidden-course').val();
    if(isCourseHidden)
    {
        $('#unhidelink').show();
    }else{
        $('#unhidelink').hide();
    }
});

function hidefromcourselist(courseid, el){
    var allMessage = {courseId: courseid};
    var html = '<div><p>Are you SURE you want to hide this course from your course list?</p></div>';
    var cancelUrl = $(this).attr('href');
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                $(this).dialog('destroy').remove();
                jQuerySubmit('hide-from-course-list', allMessage);
                $(el).parent().slideUp();
                $('#unhidelink').show();
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });


}