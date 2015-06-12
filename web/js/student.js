$(document).ready(function(){
    /**
     *  Display Calender
     */
    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();
    calendar();

    /**
     * Show Dialog Pop Up for Assessment time
     */
    $('.confirmation-require').click(function(e){

        var linkId = $(this).attr('id');
        var timelimit = Math.abs($('#time-limit'+linkId).val());
        var hour = (Math.floor(timelimit/3600) < 10) ? '0'+Math.floor(timelimit/3600) : Math.floor(timelimit/3600);
        var min = Math.floor((timelimit%3600)/60);
        var html = '<div>This assessment has a time limit of '+hour+' hour, '+min+' minutes.  Click OK to start or continue working on the assessment.</div>';
        var cancelUrl = $(this).attr('href');
        e.preventDefault();
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

/**
 *  Display Calender
 */
function calendar() {
    var htmlMsg = "<div>Assessment</div>";
    var courseId = $('.calender-course-id').val();
    var now = $('.current-time').val();
    var reviewDate = $('.review-date').val();
    var endDate = $('.end-date').val();
    var startDate = $('.start-date').val();

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
                        var eventColor = 'blue';

                        if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString != 0 && assessmentDetail.reviewDateString > assessmentDetail.now)
                        {
                            eventColor = 'red';
                        }
                        /**
                         * If assessment is in review mode, event
                         */
                        if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString != 0 && assessmentDetail.reviewDateString > assessmentDetail.now)
                        {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.reviewDate,
                                reviewDat: assessmentDetail.reviewDate,
                                end:assessmentDetail.endDate,
                                message: 'Review Assessment',
                                courseId: assessmentDetail.courseId,
                                assessmentId: assessmentDetail.assessmentId,
                                color: eventColor,
                                reviewMode: true

                            });

                        }
                        /**
                         * If assessment is not in review mode, event
                         */
                        else if(assessmentDetail.endDateString > assessmentDetail.now && assessmentDetail.startDateString < assessmentDetail.now)
                        {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.endDate,
                                startDat: assessmentDetail.endDate,
                                courseId: assessmentDetail.courseId,
                                assessmentId: assessmentDetail.assessmentId,
                                message: 'Assessment',
                                color: eventColor
                            });
                        }
                    });
                    callback(events);
                }
            });
        },
        eventClick:  function(event, jsEvent, view) {
            /**
             * If assessment is in review mode, dialog pop up
             */
            if(event.reviewMode == true)
            {
                $("#demo").empty();
                var dateH = "Showing as Review until <b>" +event.reviewDat+"</b>";
                var reviewMode = "This assessment is in review mode - no scores will be saved";
                var assessmentLogo = "<img alt='assess' class='floatleft' src='../../img/assess.png'/>";
                $("#demo").append("<div> "+assessmentLogo+" "+event.title+"<br>"+dateH+"<br>"+reviewMode+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350});
            }
            /**
             * If assessment is not in review mode
             */
            else
            {
                $("#demo").empty();
                var title = "<a href='../../assessment/assessment/show-assessment?id="+event.assessmentId+" '>"+event.title+"</a>";
                var dateH = "Due " +event.startDat+"";
                var assessmentLogo = "<img alt='assess' class='floatleft' src='../../img/assess.png'/>";
                $("#demo").append("<div> "+assessmentLogo+" "+title+"<br>"+dateH+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350});
            }
            $(this).close();
        }
    });
}


//Response of ajax for calendar
function getAssessmentDataRequest(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var tempArray = [];
        var courseData = result.data;
        $.each(courseData, function (index, temp) {
            $.each(temp, function (index, singleValue) {
                tempArray.push(singleValue);
            });
            calendar(tempArray);
        });
    }
}
