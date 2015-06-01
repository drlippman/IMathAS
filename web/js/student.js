$(document).ready(function(){

//    Display Calender
    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();

    var events_array = [
        {
            img: 'img/assess.png',
            title: 'All Day Event',
            start: new Date(y, m, d)
        },
        {
            title: 'Long Event',
            start: new Date(y, m, d+5)
        }
    ];
    $('.calendar').fullCalendar({

        height: 400,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: false,
        events: events_array,
        eventRender: function(event, element, calEvent) {
            element.find(".fc-event-title").after($("<span class=\"fc-event-icons\"></span>").html("<img src=\"/img/assess.png\" />"));
        }
    });

    $('.fc-event').on('click','img',function(event) {
        alert($(this).attr('src'));
    });

//        Show Dialog Pop Up for Assessment time

    $('.confirmation-require').click(function(e){

        var linkId = $(this).attr('id')
        var timelimit = Math.abs($('#time-limit'+linkId).val());
        var hour = (Math.floor(timelimit/3600) < 10) ? '0'+Math.floor(timelimit/3600) : Math.floor(timelimit/3600);
        var min = Math.floor((timelimit%3600)/60);
        var html = '<div>This assessment has a time limit of '+hour+' hour, '+min+' minutes.  Click OK to start or continue working on the assessment.</div>';
        var cancelUrl = $(this).attr('href');
        e.preventDefault();
        $('<div  id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "confirm": function () {
                    window.location = cancelUrl;
                    $(this).dialog("close");
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });

    });

});
