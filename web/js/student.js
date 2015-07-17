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
        var html = '<div>This assessment has a time limit of '+hour+' hour, '+min+' minutes.  Click confirm to start or continue working on the assessment.</div>';
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
                "Confirm": function () {
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
        height: 420,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: false,
        events: function (start, end, timezone, callback) {
            var html = '';
            var moment = $('.calendar').fullCalendar('getDate');
            $.ajax({
                url: 'get-assessment-data-ajax',
                data: {
                    cid: courseId
                },
                success: function (response) {
                   var calendarResponse = JSON.parse(response);
                    var assessmentData = calendarResponse.data;
                    var events = [];
                    $.each(assessmentData.assessmentArray, function (index, assessmentDetail) {
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
                                color: eventColor,
                                reviewMode: false
                            });
                        }
                    });
                    /**
                     * Display Managed events by admin
                     */
                    $.each(assessmentData.calendarArray, function (index, calendarItem) {
                        var eventColor = '#00FFCC';
                        if(calendarItem != 0)
                        {
                            events.push({
                                title: calendarItem.tag,
                                start: calendarItem.date,
                                tagTitle:calendarItem.title,
                                message: 'Managed Events',
                                color: eventColor,
                                calItem: true
                            });
                        }
                    });
                    /**
                     * Display Linked text's tag on enddate with title as URL
                     */
                    $.each(assessmentData.calendarLinkArray, function (index, calendarLinkItem) {
                        var eventColor = '#59FF59';
                        if(calendarLinkItem.startDateString < calendarLinkItem.now && calendarLinkItem.endDateString > calendarLinkItem.now)
                        {
                            events.push({
                                title: calendarLinkItem.calTag,
                                linkTitle: calendarLinkItem.title,
                                start: calendarLinkItem.endDate,
                                linkedId: calendarLinkItem.linkedId,
                                color: eventColor,
                                courseId: calendarLinkItem.courseId,
                                message: 'Linked text events',
                                calLinkItem: true

                            });
                        }
                    });
                    /**
                     * Display Inline text on calendar
                     */
                    $.each(assessmentData.calendarInlineTextArray, function (index, calendarInlineTextItem) {
                        var eventColor = '#FF6666';
                        if(calendarInlineTextItem.startDateString < calendarInlineTextItem.now && calendarInlineTextItem.endDateString > calendarInlineTextItem.now)
                        {
                            events.push({
                                title: calendarInlineTextItem.calTag,
                                start: calendarInlineTextItem.endDate,
                                color: eventColor,
                                message: 'Inline text events',
                                calInlineTextItem: true

                            });
                        }
                    });
                    callback(events);
                }
            });
//            if(moment == assessmentData.assessmentArray){
//                html += moment;
//                $('.calendar-day-details').append(html);
//            }
            alert(assessmentData);

        },
        /**
         * Onclick event
         */
        eventClick:  function(event, jsEvent, view) {
            /**
             * If assessment is in review mode, dialog pop up
             */
            if(event.reviewMode == true)
            {
                $("#demo").empty();
                var dateH = "Showing as Review until <b>" +event.reviewDat+"</b>";
                var reviewMode= "<p style='margin-left:21px!important;'>This assessment is in review mode - no scores will be saved</p>";
                var assessmentLogo = "<img alt='assess' class='floatleft' src='../../img/assess.png'/>";
                $("#demo").append("<div>"+assessmentLogo+"<b> "+event.title+"</b><br>"+dateH+"."+reviewMode+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog('close');
                            return false;
                        }
                    }
                });
            }
            /**
             * If assessment is not in review mode
             */
            else if(event.reviewMode == false)
            {
                $("#demo").empty();
                var title = "<a class='link'style='color: #0000ff' href='../../assessment/assessment/show-assessment?id="+event.assessmentId+"&cid="+event.courseId+" '>"+event.title+"</a>";
                var dateH = "Due " +event.startDat+"";
                var assessmentLogo = "<img alt='assess' class='floatleft' src='../../img/assess.png'/>";
                $("#demo").append("<div> "+assessmentLogo+" "+title+"<br>"+dateH+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog('close');
                            return false;
                        }
                    }
                });
            }
            /**
             * Managed event by admin pop up.
             */
            else if(event.calItem == true)
            {
                $("#demo").empty();
                var title = event.tagTitle;
                var tag = event.title;
                $("#demo").append("<div> "+tag+"<br>"+title+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog('close');
                            return false;
                        }
                    }
                });
            }
            else if(event.calLinkItem == true)
            {
                $("#demo").empty();
                var tag = event.title;
                var title = "<a class='link'style='color: #0000ff' href='../../course/course/show-linked-text?cid="+event.courseId+"&id="+event.linkedId+" '>"+event.linkTitle+"</a>";
                $("#demo").append("<div> "+tag+"<br>"+title+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog('close');
                            return false;
                        }
                    }
                });
            }
            else if(event.calInlineTextItem == true)
            {
                $("#demo").empty();
                var tag = event.title;
                $("#demo").append("<div> "+tag+"</div>");
                $("#demo").dialog({ modal: true, title: event.message, width:350,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog('close');
                            return false;
                        }
                    }
                });
            }
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
