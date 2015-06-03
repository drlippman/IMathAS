$(document).ready(function(){

//    Display Calender
    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();
    calendar();

//        Show Dialog Pop Up for Assessment time

    $('.confirmation-require').click(function(e){

        var linkId = $(this).attr('id');
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
// Display calendar
    function calendar() {
       // var html = "<div>Assessment</div>";
        var courseId = $('.calender-course-id').val();
        $('.calendar').fullCalendar({
            height: 400,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            editable: false,
            events: function (start, end, timezone, callback) {
                $.ajax({
                    url: 'get-assessment-data-ajax',
                    data: {
                        cid: courseId
                    },
                    success: function (response) {
                        response = JSON.parse(response);
                        var assessmentData = response.data;
                        var events = [];

                        $.each(assessmentData, function (index, assessmentDetail) {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.endDate
                            });
                        });
                        callback(events);
                    }

                });
            },
            eventClick:  function(event, jsEvent, view) {
                //set the values and open the modal pop up
                $('.calendar').html(event.html);
                $('.modal-pop-up-assessment').attr(event,'javascript:void(0);');
                $('.modal-pop-up-assessment').dialog({ modal: true, title: event.title,width:350});
            }

        });
    }

//Response of ajax for calendar
    function getAssessmentDataRequest(response) {
        var result = JSON.parse(response);
        if (result.status == 0) {
            var tempArray = [];
            var courseData = result.data;
            alert(courseData);
            $.each(courseData, function (index, temp) {
                $.each(temp, function (index, singleValue) {
                    tempArray.push(singleValue);
                });
                calendar(tempArray);
            });
        }
    }
