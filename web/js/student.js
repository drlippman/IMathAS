jQuery(document).ready(function(){
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

    jQuery('.confirmation-require').click(function(e){
        var linkId = jQuery(this).attr('id');
        var timelimit = Math.abs(jQuery('#time-limit'+linkId).val());
        var hour = (Math.floor(timelimit/3600) < 10) ? '0'+Math.floor(timelimit/3600) : Math.floor(timelimit/3600);
        var min = Math.floor((timelimit%3600)/60);
        var html = '<div>This assessment has a time limit of '+hour+' hour, '+min+' minutes.  Click confirm to start or continue working on the assessment.</div>';
        var cancelUrl = jQuery(this).attr('href');

        e.preventDefault();
        jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    jQuery(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    window.location = cancelUrl;
                    jQuery(this).dialog("close");
                    return true;
                }
            },
            close: function (event, ui) {
                jQuery(this).remove();
            }
        });

    });
});
var calendarEvents = [];
/**
 *  Display Calender
 */
function calendar() {
    var htmlMsg = "<div>Assessment</div>";
    var courseId = jQuery('.calender-course-id').val();
    var now = jQuery('.current-time').val();
    var reviewDate = jQuery('.review-date').val();
    var endDate = jQuery('.end-date').val();
    var startDate = jQuery('.start-date').val();
    var userRights = jQuery('.user-rights').val();
    jQuery('.calendar').fullCalendar({
        height: "auto",
        fixedWeekCount: false,
        header: {
            left: 'prev,  title, next ',
            right: 'month,agendaWeek'
        },
        editable: false,
        events: function (start, end, timezone, callback) {
            var html = '';
            var moment = jQuery('.calendar').fullCalendar('getDate');
            jQuery.ajax({
                url: 'get-assessment-data-ajax',
                data: {
                    cid: courseId
                },
                success: function (response) {
                    var calendarResponse = JSON.parse(response);
                    var assessmentData = calendarResponse.data;

                    var events = [];
                    jQuery.each(assessmentData.assessmentArray, function (index, assessmentDetail) {

                        var eventColor = 'blue';
                        if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > now)
                        {
                            eventColor = 'red';
                        } else  if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString == 2000000000)
                        {
                            eventColor = 'red';
                        } else if(assessmentDetail.endDateString < now && assessmentDetail.startDateString < now){
                            eventColor = 'grey';
                        }
                        /**
                         * If assessment is in review mode, event
                         */
                        if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > now)
                        {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.reviewDate,
                                dueTime: assessmentDetail.dueTime,
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
                                dueTime: assessmentDetail.dueTime,
                                startDat: assessmentDetail.endDate,
                                courseId: assessmentDetail.courseId,
                                assessmentId: assessmentDetail.assessmentId,
                                message: 'Assessment',
                                color: eventColor,
                                reviewMode: false
                            });
                        } else if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString == 2000000000)
                        {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.endDate,
                                dueTime: assessmentDetail.dueTime,
                                end:assessmentDetail.endDate,
                                message: 'Review Assessment',
                                courseId: assessmentDetail.courseId,
                                assessmentId: assessmentDetail.assessmentId,
                                color: eventColor,
                                reviewModeDueDate: true
                            });
                        } else if(assessmentDetail.endDateString < now && assessmentDetail.startDateString < now)
                        {
                            events.push({
                                title: assessmentDetail.name,
                                start: assessmentDetail.endDate,
                                dueTime: assessmentDetail.dueTime,
                                end:assessmentDetail.endDate,
                                message: 'Review Assessment',
                                courseId: assessmentDetail.courseId,
                                assessmentId: assessmentDetail.assessmentId,
                                color: eventColor,
                                closeMode: true
                            });
                        }
                    });
                    /**
                     * Display Managed events by admin
                     */
                    jQuery.each(assessmentData.calendarArray, function (index, calendarItem) {

                        var eventColor = '#00FFCC';
                        if(calendarItem != 0)
                        {
                            events.push({
                                title: calendarItem.tag,
                                start: calendarItem.date,
                                tagTitle:calendarItem.title,
                                dueTime: calendarItem.dueTime,
                                message: 'Managed Events',
                                color: eventColor,
                                calItem: true
                            });
                        }
                    });
                    /**
                     * Display Linked text's tag on enddate with title as URL
                     */
                    jQuery.each(assessmentData.calendarLinkArray, function (index, calendarLinkItem) {
                        var eventColor = '#59FF59';
                        if(calendarLinkItem.endDateString < now && calendarLinkItem.startDateString < now)
                        {
                            eventColor = 'grey';
                        }

                        if(calendarLinkItem.oncal == 2 && calendarLinkItem.avail == 1) {
                            if(calendarLinkItem.notStudent == true)
                            {
                                if(calendarLinkItem.userRights > 10){
                                    events.push({
                                        title: calendarLinkItem.calTag,
                                        linkTitle: calendarLinkItem.title,
                                        start: calendarLinkItem.endDate,
                                        linkedId: calendarLinkItem.linkedId,
                                        dueTime: calendarLinkItem.dueTime,
                                        id: calendarLinkItem.id,
                                        oncal:calendarLinkItem.oncal,
                                        text:calendarLinkItem.text,
                                        textType:calendarLinkItem.textType,
                                        avail:calendarLinkItem.avail,
                                        color: eventColor,
                                        courseId: calendarLinkItem.courseId,
                                        message: 'Linked text events',
                                        calLinkItemOnCal: true
                                    });
                                }
                            } else if(calendarLinkItem.notStudent == false){
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.endDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    oncal:calendarLinkItem.oncal,
                                    text:calendarLinkItem.text,
                                    textType:calendarLinkItem.textType,
                                    avail:calendarLinkItem.avail,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItemOnCal: true
                                });
                            }
                        } else if(calendarLinkItem.oncal == 1 && calendarLinkItem.avail == 1){
                            if(calendarLinkItem.notStudent == true)
                            {
                                if(calendarLinkItem.userRights > 10){
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.startDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    textType:calendarLinkItem.textType,
                                    avail:calendarLinkItem.avail,
                                    oncal:calendarLinkItem.oncal,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItem: true
                                });
                               }
                            } else if(calendarLinkItem.notStudent == false){
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.startDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    textType:calendarLinkItem.textType,
                                    avail:calendarLinkItem.avail,
                                    oncal:calendarLinkItem.oncal,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItem: true
                                });
                            }

                        } else if(calendarLinkItem.oncal == 1 && calendarLinkItem.avail == 2){
                            if(calendarLinkItem.notStudent == true)
                            {
                                if(calendarLinkItem.userRights > 10){
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.startDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    textType:calendarLinkItem.textType,
                                    avail:calendarLinkItem.avail,
                                    oncal:calendarLinkItem.oncal,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItemAvail: true
                                });
                            }
                        } else if(calendarLinkItem.notStudent == false){
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.startDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    textType:calendarLinkItem.textType,
                                    avail:calendarLinkItem.avail,
                                    oncal:calendarLinkItem.oncal,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItemAvail: true
                                });
                            }
                        }
                    });
                    /**
                     * Display Inline text on calendar
                     */
                    jQuery.each(assessmentData.calendarInlineTextArray, function (index, calendarInlineTextItem) {
                        var eventColor = '#FF6666';
                        if(calendarInlineTextItem.endDateString < now && calendarInlineTextItem.startDateString < now)
                        {
                            eventColor = 'grey';
                        }
                        if(calendarInlineTextItem.oncal == 2 && calendarInlineTextItem.avail == 1){
                                if(calendarInlineTextItem.notStudent == true)
                                {
                                    if(calendarInlineTextItem.userRights > 10){
                                        events.push({
                                            title: calendarInlineTextItem.calTag,
                                            start: calendarInlineTextItem.endDate,
                                            dueTime: calendarInlineTextItem.dueTime,
                                            courseId: calendarInlineTextItem.courseId,
                                            id: calendarInlineTextItem.id,
                                            oncal:calendarInlineTextItem.oncal,
                                            avail:calendarInlineTextItem.avail,
                                            name:calendarInlineTextItem.title,
                                            now:calendarInlineTextItem.now,
                                            end:calendarInlineTextItem.start,
                                            userCheck:calendarInlineTextItem.notStudent,
                                            color: eventColor,
                                            message: 'Inline text events',
                                            calInlineTextOncal: true
                                        });
                                    }
                                } else if(calendarInlineTextItem.notStudent == false){
                                    events.push({
                                        title: calendarInlineTextItem.calTag,
                                        start: calendarInlineTextItem.endDate,
                                        dueTime: calendarInlineTextItem.dueTime,
                                        courseId: calendarInlineTextItem.courseId,
                                        id: calendarInlineTextItem.id,
                                        oncal:calendarInlineTextItem.oncal,
                                        avail:calendarInlineTextItem.avail,
                                        name:calendarInlineTextItem.title,
                                        now:calendarInlineTextItem.now,
                                        end:calendarInlineTextItem.start,
                                        userCheck:calendarInlineTextItem.notStudent,
                                        color: eventColor,
                                        message: 'Inline text events',
                                        calInlineTextOncal: true
                                    });
                                }
                        } else if(calendarInlineTextItem.oncal == 1 && calendarInlineTextItem.avail == 1){
                            if(calendarInlineTextItem.notStudent == true)
                            {
                                if(calendarInlineTextItem.userRights > 10){
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.startDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    avail:calendarInlineTextItem.avail,
                                    name:calendarInlineTextItem.title,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextItem: true
                                });
                              }
                            } else if(calendarInlineTextItem.notStudent == false){
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.startDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    avail:calendarInlineTextItem.avail,
                                    name:calendarInlineTextItem.title,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextItem: true
                                });
                            }
                        } else if(calendarInlineTextItem.oncal == 1 && calendarInlineTextItem.avail == 2){
                            if(calendarInlineTextItem.notStudent == true)
                            {
                                if(calendarInlineTextItem.userRights > 10){
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.startDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    avail:calendarInlineTextItem.avail,
                                    name:calendarInlineTextItem.title,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextItemAvail: true
                                });
                              }
                            } else if(calendarInlineTextItem.notStudent == false){
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.startDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    avail:calendarInlineTextItem.avail,
                                    name:calendarInlineTextItem.title,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextItemAvail: true
                                });
                            }
                        }

                    });
                    jQuery(".day-details").empty();
                    jQuery(".day-details").append("<div class='day-details'> " +moment.format('dddd MMMM DD, YYYY')+ "</div>");
                    jQuery(".calendar-day-details").empty();
                    displayCalEvents(events);
                    calendarEvents.push(events);
                    callback(events);
                }
            });
        },

        dayClick: function(date, jsEvent, view) {
            var currentDate = getCurrentDate();
            var selectedDay = date.format();
            var formattedSelectedDay = formatDate(selectedDay);
            var settings = 'Settings';
            var questions = 'Questions';
            var grades = 'Grades';
            var modify = 'Modify';
            jQuery(this).addClass('ac_checked_date');
            jQuery('.fc-day').not(jQuery(this)).removeClass('ac_checked_date');
            jQuery(".calendar-day-details").empty();
            jQuery(".day-details").empty();

            jQuery('.calendar').fullCalendar('clientEvents', function(event) {

                var clickedDate = date;

                if(clickedDate >= event.start && clickedDate <= event.end) {
                }
            });
            jQuery(".day-details").append("<div class='day-details'> "+ date.format('dddd MMMM DD, YYYY')+"</div>");

            jQuery.each(calendarEvents[0], function (index, selectedDate) {

                if(selectedDate.start == selectedDay) {
                    if(currentDate == formattedSelectedDay){
                        var dateH = "Due " +selectedDate.dueTime+"";
                    }else {
                        var dateH = "Due " +selectedDate.start+" "+selectedDate.dueTime+"";
                    }
                    if(selectedDate.reviewMode == false){
                        var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+selectedDate.title+"</a>";
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                        if(userRights > 10){
                            var dropdown = '<span class="instronly common-setting-calendar">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                                "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                                "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                                "</ul>"+
                                "</span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        } else {
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }
                    }  else if(selectedDate.calLinkItem == true){

                        var tag = selectedDate.title;
                        var str = selectedDate.text;

                        var title = "<a class='link-title' style='color: #0000ff' href=' "+selectedDate.textType+" '>"+selectedDate.linkTitle+"</a>";

                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/add-link?cid="+selectedDate.courseId+"&id="+selectedDate.id+"'>Modify</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                        }
                    } else if(selectedDate.calLinkItemAvail == true){
                        var tag = selectedDate.title;
                        var str = selectedDate.text;

                        var title = "<a class='link-title' style='color: #0000ff' href=' "+selectedDate.textType+" '>"+selectedDate.linkTitle+"</a>";

                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/add-link?cid="+selectedDate.courseId+"&id="+selectedDate.id+"'>Modify</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                        }
                    }else if(selectedDate.calLinkItemOnCal == true){
                        var tag = selectedDate.title;
                        var str = selectedDate.text;
                        var title = "<a class='link-title' style='color: #0000ff' href=' "+selectedDate.textType+" '>"+selectedDate.linkTitle+"</a>";
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/add-link?cid="+selectedDate.courseId+"&id="+selectedDate.id+"'>Modify</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                        }
                    } else if(selectedDate.calInlineTextOncal == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                        var tag = selectedDate.title;
                        var title = selectedDate.name;
                        var name = "<a href='../../course/course/course?cid="+selectedDate.courseId+" '>"+title+"</a>";

                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/modify-inline-text?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p>"+html+"<br><p style='padding-left: 36px;>"+name+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                        }
                    }  else if(selectedDate.calInlineTextItem == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                        var tag = selectedDate.title;
                        var title = selectedDate.name;
                        var name = "<a href='../../course/course/course?cid="+selectedDate.courseId+"'>"+title+ "</a>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/modify-inline-text?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p>"+html+"<br><p style='padding-left: 36px;'>"+name+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                        }
                    } else if(selectedDate.calInlineTextItemAvail == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                        var tag = selectedDate.title;
                        var title = selectedDate.name;
                        var name = "<a href='../../course/course/course?cid="+selectedDate.courseId+"'>"+title+ "</a>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/modify-inline-text?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p>"+html+"<br><p style='padding-left: 36px;'>"+name+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                        }
                    } else if(selectedDate.calItem == true){
                        var title = selectedDate.tagTitle;
                        var date = selectedDate.start;
                        var assessmentLogo = "<img alt='' class='floatleft item-icon-alignment' style='outline: none' src=''/>";
                        var tag = selectedDate.title;
                        jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<b>"+tag+"</b><br>"+title+"<br><p style='padding-left: 36px'>"+date+"</p></div>");
                    } else if(selectedDate.reviewMode == true){
                        var dateH = "Review until <b>" +selectedDate.reviewDat+"</b>";
                        var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+selectedDate.title+"</a>";
                        var reviewMode= "<p style='margin-left:35px!important;padding-top: 0'>This assessment is in review mode - no scores will be saved</p>";
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                        if(userRights > 10){
                            var dropdown = '<span class="instronly common-setting-calendar">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                                "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                                "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                                "</ul>"+
                                "</span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+title+" "+dropdown+"</b><br>"+dateH+"."+reviewMode+"</div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+title+"</b><br>"+dateH+"."+reviewMode+"</div>");
                        }
                    } else if(selectedDate.reviewModeDueDate == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                        var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+selectedDate.title+"</a>";
                        if(userRights > 10){
                            var dropdown = '<span class="instronly common-setting-calendar">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                                "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                                "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                                "</ul>"+
                                "</span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"<br>"+dateH+"</div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"<br>"+dateH+"</div>");
                        }
                    }else if(selectedDate.closeMode == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                        var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+selectedDate.title+"</a>";
                        if(userRights > 10){
                            var dropdown = '<span class="instronly common-setting-calendar">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                                "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                                "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                                "</ul>"+
                                "</span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"</b><br>"+dateH+"</div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"</b><br>"+dateH+"</div>");
                        }
                    }
                }
            });
        }
    });

}
/**
    Displayed All Events of a Month on a click Event
 **/
function ShowAll() {
    var now = getCurrentDate();
    var courseId = jQuery('.calender-course-id').val();
    var userRights = jQuery('.user-rights').val();
    var settings = 'Settings';
    var questions = 'Questions';
    var grades = 'Grades';
    var modify = 'Modify';
    var moment = jQuery('.calendar').fullCalendar('getDate');
    var moment_month = moment.month() + 1;
    jQuery(".day-details").empty();
    jQuery(".calendar-day-details").empty();
    jQuery(".day-details").append("<div class='day-details'> " +moment.format('MMMM, YYYY')+ "</div>");
    jQuery.ajax({
        url: 'get-assessment-data-ajax',
        data: {
            cid: courseId
        },
        success: function (response) {
            var calendarResponse = JSON.parse(response);
            var assessmentData = calendarResponse.data;
            var events = [];
            jQuery.each(assessmentData.assessmentArray, function (index, assessmentDetail) {

                var eventColor = 'blue';
                if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > now)
                {
                    eventColor = 'red';
                } else  if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString == 2000000000)
                {
                    eventColor = 'red';
                } else if(assessmentDetail.endDateString < now && assessmentDetail.startDateString < now){
                    eventColor = 'grey';
                }
                /**
                 * If assessment is in review mode, event
                 */
                if(assessmentDetail.endDateString < now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > now)
                {

                    events.push({
                        title: assessmentDetail.name,
                        start: assessmentDetail.reviewDate,
                        dueTime: assessmentDetail.dueTime,
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
                        dueTime: assessmentDetail.dueTime,
                        startDat: assessmentDetail.endDate,
                        courseId: assessmentDetail.courseId,
                        assessmentId: assessmentDetail.assessmentId,
                        message: 'Assessment',
                        color: eventColor,
                        reviewMode: false
                    });
                } else if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString == 2000000000)
                {
                    events.push({
                        title: assessmentDetail.name,
                        start: assessmentDetail.endDate,
                        dueTime: assessmentDetail.dueTime,
                        end:assessmentDetail.endDate,
                        message: 'Review Assessment',
                        courseId: assessmentDetail.courseId,
                        assessmentId: assessmentDetail.assessmentId,
                        color: eventColor,
                        reviewModeDueDate: true
                    });
                } else if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.startDateString < assessmentDetail.now)
                {
                    events.push({
                        title: assessmentDetail.name,
                        start: assessmentDetail.endDate,
                        dueTime: assessmentDetail.dueTime,
                        end:assessmentDetail.endDate,
                        message: 'Review Assessment',
                        courseId: assessmentDetail.courseId,
                        assessmentId: assessmentDetail.assessmentId,
                        color: eventColor,
                        closeMode: true
                    });
                }
            });
            /**
             * Display Managed events by admin
             */
            jQuery.each(assessmentData.calendarArray, function (index, calendarItem) {

                var eventColor = '#00FFCC';
                if(calendarItem != 0)
                {
                    events.push({
                        title: calendarItem.tag,
                        start: calendarItem.date,
                        tagTitle:calendarItem.title,
                        dueTime: calendarItem.dueTime,
                        message: 'Managed Events',
                        color: eventColor,
                        calItem: true
                    });
                }
            });            /**
             * Display Linked text's tag on enddate with title as URL
             */
            jQuery.each(assessmentData.calendarLinkArray, function (index, calendarLinkItem) {
                var eventColor = '#59FF59';
                if(calendarLinkItem.oncal == 2 && calendarLinkItem.avail == 1) {
                    if(calendarLinkItem.notStudent == true)
                    {
                        if(calendarLinkItem.userRights > 10){
                            events.push({
                                title: calendarLinkItem.calTag,
                                linkTitle: calendarLinkItem.title,
                                start: calendarLinkItem.endDate,
                                linkedId: calendarLinkItem.linkedId,
                                dueTime: calendarLinkItem.dueTime,
                                id: calendarLinkItem.id,
                                oncal:calendarLinkItem.oncal,
                                text:calendarLinkItem.text,
                                textType:calendarLinkItem.textType,
                                avail:calendarLinkItem.avail,
                                color: eventColor,
                                courseId: calendarLinkItem.courseId,
                                message: 'Linked text events',
                                calLinkItemOnCal: true
                            });
                        }
                    } else if(calendarLinkItem.notStudent == false){
                        events.push({
                            title: calendarLinkItem.calTag,
                            linkTitle: calendarLinkItem.title,
                            start: calendarLinkItem.endDate,
                            linkedId: calendarLinkItem.linkedId,
                            dueTime: calendarLinkItem.dueTime,
                            id: calendarLinkItem.id,
                            oncal:calendarLinkItem.oncal,
                            text:calendarLinkItem.text,
                            textType:calendarLinkItem.textType,
                            avail:calendarLinkItem.avail,
                            color: eventColor,
                            courseId: calendarLinkItem.courseId,
                            message: 'Linked text events',
                            calLinkItemOnCal: true
                        });
                    }
                } else if(calendarLinkItem.oncal == 1 && calendarLinkItem.avail == 1){
                    if(calendarLinkItem.notStudent == true)
                    {
                        if(calendarLinkItem.userRights > 10){
                            events.push({
                                title: calendarLinkItem.calTag,
                                linkTitle: calendarLinkItem.title,
                                start: calendarLinkItem.startDate,
                                linkedId: calendarLinkItem.linkedId,
                                dueTime: calendarLinkItem.dueTime,
                                id: calendarLinkItem.id,
                                textType:calendarLinkItem.textType,
                                avail:calendarLinkItem.avail,
                                oncal:calendarLinkItem.oncal,
                                color: eventColor,
                                courseId: calendarLinkItem.courseId,
                                message: 'Linked text events',
                                calLinkItem: true
                            });
                        }
                    } else if(calendarLinkItem.notStudent == false){
                        events.push({
                            title: calendarLinkItem.calTag,
                            linkTitle: calendarLinkItem.title,
                            start: calendarLinkItem.startDate,
                            linkedId: calendarLinkItem.linkedId,
                            dueTime: calendarLinkItem.dueTime,
                            id: calendarLinkItem.id,
                            textType:calendarLinkItem.textType,
                            avail:calendarLinkItem.avail,
                            oncal:calendarLinkItem.oncal,
                            color: eventColor,
                            courseId: calendarLinkItem.courseId,
                            message: 'Linked text events',
                            calLinkItem: true
                        });
                    }

                } else if(calendarLinkItem.oncal == 1 && calendarLinkItem.avail == 2){
                    if(calendarLinkItem.notStudent == true)
                    {
                        if(calendarLinkItem.userRights > 10){
                            events.push({
                                title: calendarLinkItem.calTag,
                                linkTitle: calendarLinkItem.title,
                                start: calendarLinkItem.startDate,
                                linkedId: calendarLinkItem.linkedId,
                                dueTime: calendarLinkItem.dueTime,
                                id: calendarLinkItem.id,
                                textType:calendarLinkItem.textType,
                                avail:calendarLinkItem.avail,
                                oncal:calendarLinkItem.oncal,
                                color: eventColor,
                                courseId: calendarLinkItem.courseId,
                                message: 'Linked text events',
                                calLinkItemAvail: true
                            });
                        }
                    } else if(calendarLinkItem.notStudent == false){
                        events.push({
                            title: calendarLinkItem.calTag,
                            linkTitle: calendarLinkItem.title,
                            start: calendarLinkItem.startDate,
                            linkedId: calendarLinkItem.linkedId,
                            dueTime: calendarLinkItem.dueTime,
                            id: calendarLinkItem.id,
                            textType:calendarLinkItem.textType,
                            avail:calendarLinkItem.avail,
                            oncal:calendarLinkItem.oncal,
                            color: eventColor,
                            courseId: calendarLinkItem.courseId,
                            message: 'Linked text events',
                            calLinkItemAvail: true
                        });
                    }
                }
            });
            /**
             * Display Inline text on calendar
             */
            jQuery.each(assessmentData.calendarInlineTextArray, function (index, calendarInlineTextItem) {
                var eventColor = '#FF6666';
                if(calendarInlineTextItem.endDateString < now && calendarInlineTextItem.startDateString < now)
                {
                    eventColor = 'grey';
                }
                if(calendarInlineTextItem.oncal == 2 && calendarInlineTextItem.avail == 1){
                    if(calendarInlineTextItem.notStudent == true)
                    {
                        if(calendarInlineTextItem.userRights > 10){
                            events.push({
                                title: calendarInlineTextItem.calTag,
                                start: calendarInlineTextItem.endDate,
                                dueTime: calendarInlineTextItem.dueTime,
                                courseId: calendarInlineTextItem.courseId,
                                id: calendarInlineTextItem.id,
                                oncal:calendarInlineTextItem.oncal,
                                avail:calendarInlineTextItem.avail,
                                name:calendarInlineTextItem.title,
                                now:calendarInlineTextItem.now,
                                end:calendarInlineTextItem.start,
                                userCheck:calendarInlineTextItem.notStudent,
                                color: eventColor,
                                message: 'Inline text events',
                                calInlineTextOncal: true
                            });
                        }
                    } else if(calendarInlineTextItem.notStudent == false){
                        events.push({
                            title: calendarInlineTextItem.calTag,
                            start: calendarInlineTextItem.endDate,
                            dueTime: calendarInlineTextItem.dueTime,
                            courseId: calendarInlineTextItem.courseId,
                            id: calendarInlineTextItem.id,
                            oncal:calendarInlineTextItem.oncal,
                            avail:calendarInlineTextItem.avail,
                            name:calendarInlineTextItem.title,
                            now:calendarInlineTextItem.now,
                            end:calendarInlineTextItem.start,
                            userCheck:calendarInlineTextItem.notStudent,
                            color: eventColor,
                            message: 'Inline text events',
                            calInlineTextOncal: true
                        });
                    }
                } else if(calendarInlineTextItem.oncal == 1 && calendarInlineTextItem.avail == 1){
                    if(calendarInlineTextItem.notStudent == true)
                    {
                        if(calendarInlineTextItem.userRights > 10){
                            events.push({
                                title: calendarInlineTextItem.calTag,
                                start: calendarInlineTextItem.startDate,
                                dueTime: calendarInlineTextItem.dueTime,
                                courseId: calendarInlineTextItem.courseId,
                                id: calendarInlineTextItem.id,
                                oncal:calendarInlineTextItem.oncal,
                                avail:calendarInlineTextItem.avail,
                                name:calendarInlineTextItem.title,
                                color: eventColor,
                                message: 'Inline text events',
                                calInlineTextItem: true
                            });
                        }
                    } else if(calendarInlineTextItem.notStudent == false){
                        events.push({
                            title: calendarInlineTextItem.calTag,
                            start: calendarInlineTextItem.startDate,
                            dueTime: calendarInlineTextItem.dueTime,
                            courseId: calendarInlineTextItem.courseId,
                            id: calendarInlineTextItem.id,
                            oncal:calendarInlineTextItem.oncal,
                            avail:calendarInlineTextItem.avail,
                            name:calendarInlineTextItem.title,
                            color: eventColor,
                            message: 'Inline text events',
                            calInlineTextItem: true
                        });
                    }
                } else if(calendarInlineTextItem.oncal == 1 && calendarInlineTextItem.avail == 2){
                    if(calendarInlineTextItem.notStudent == true)
                    {
                        if(calendarInlineTextItem.userRights > 10){
                            events.push({
                                title: calendarInlineTextItem.calTag,
                                start: calendarInlineTextItem.startDate,
                                dueTime: calendarInlineTextItem.dueTime,
                                courseId: calendarInlineTextItem.courseId,
                                id: calendarInlineTextItem.id,
                                oncal:calendarInlineTextItem.oncal,
                                avail:calendarInlineTextItem.avail,
                                name:calendarInlineTextItem.title,
                                color: eventColor,
                                message: 'Inline text events',
                                calInlineTextItemAvail: true
                            });
                        }
                    } else if(calendarInlineTextItem.notStudent == false){
                        events.push({
                            title: calendarInlineTextItem.calTag,
                            start: calendarInlineTextItem.startDate,
                            dueTime: calendarInlineTextItem.dueTime,
                            courseId: calendarInlineTextItem.courseId,
                            id: calendarInlineTextItem.id,
                            oncal:calendarInlineTextItem.oncal,
                            avail:calendarInlineTextItem.avail,
                            name:calendarInlineTextItem.title,
                            color: eventColor,
                            message: 'Inline text events',
                            calInlineTextItemAvail: true
                        });
                    }
                }
            });
            calendarEvents.push(events);

    jQuery.each(events, function (index, dateEvent) {
        var selectedDate = formatDate(dateEvent.start);
        var selected_month = selectedDate.split('-');

        if(selected_month[1] == moment_month) {
                    var dateH = "Due "+ selectedDate+" | "+ dateEvent.dueTime + "";
            if(dateEvent.reviewMode == false){
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+dateEvent.courseId+"&aid="+dateEvent.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+dateEvent.courseId+"&asid=average&aid="+dateEvent.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }
            }else if(dateEvent.calLinkItem == true){
                var tag = dateEvent.title;
                var str = dateEvent.text;

                var title = "<a class='link-title'style='color: #0000ff' href='../../course/course/show-linked-text?cid="+dateEvent.courseId+"&id="+dateEvent.linkedId+" '>"+dateEvent.linkTitle+"</a>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+dropdown+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.calLinkItemAvail == true){
                var tag = dateEvent.title;
                var str = dateEvent.text;

                var title = "<a class='link-title' style='color: #0000ff' href=' "+dateEvent.textType+" '>"+dateEvent.linkTitle+"</a>";

                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var html = '<span class="instronly common-setting">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+"'>Modify</a></li>"+
                        "</ul></span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.calLinkItemOnCal == true){
                var tag = dateEvent.title;
                var str = dateEvent.text;

                var title = "<a class='link-title' style='color: #0000ff' href=' "+dateEvent.textType+" '>"+dateEvent.linkTitle+"</a>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+dropdown+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            } else if(dateEvent.calInlineTextOncal == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                var title = dateEvent.name;
                var name = "<a href='../../course/course/course?cid="+dateEvent.courseId+"'>"+title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p>"+dropdown+"<br><p style='padding-left: 36px;'>"+name+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                }

            } else if(dateEvent.calInlineTextItem == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                var title = dateEvent.name;
                var name = "<a href='../../course/course/course?cid="+dateEvent.courseId+"'>"+title+ "</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p>"+dropdown+"<p style='padding-left: 36px;'>"+name+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p><p style='padding-left: 36px;'>"+name+"</p></div>");
                }
            } else if(dateEvent.calInlineTextItemAvail == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                var title = dateEvent.name;
                var name = "<a href='../../course/course/course?cid="+dateEvent.courseId+"'>"+title+ "</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style='background-color: #FF6666; display: inline'>"+tag+"</p>"+dropdown+"<br><p style='padding-left: 36px;'>"+name+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                }
            } else if(dateEvent.calItem == true){
                var title = dateEvent.tagTitle;
                var date = dateEvent.start;
                var assessmentLogo = "<img alt='' class='floatleft item-icon-alignment' style='outline: none' src=''/>";
                var tag = dateEvent.title;
                jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br><b>"+tag+"</b><br/><p style='padding-left: 36px'>"+date+"</p></div>");
            } else if(dateEvent.reviewMode == true){
                var dateH = "Review until <b>" +dateEvent.reviewDat+"</b>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                var reviewMode= "<p style='margin-left:35px!important;padding-top: 0'>This assessment is in review mode - no scores will be saved</p>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";

                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+dateEvent.courseId+"&aid="+dateEvent.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+dateEvent.courseId+"&asid=average&aid="+dateEvent.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"</div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"</div>");

                }
            } else if(dateEvent.reviewModeDueDate == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+dateEvent.courseId+"&aid="+dateEvent.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+dateEvent.courseId+"&asid=average&aid="+dateEvent.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.closeMode == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+dateEvent.courseId+"&aid="+dateEvent.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+dateEvent.courseId+"&asid=average&aid="+dateEvent.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }

        }
    });
}
    });
   }

/**
 * Displays current day detail on load of calendar.
 * @param events
 */
function displayCalEvents(events) {
    var now = getCurrentDate();
    var userRights = jQuery('.user-rights').val();
    var settings = 'Settings';
    var questions = 'Questions';
    var grades = 'Grades';
    var modify = 'Modify';
    var moment = jQuery('.calendar').fullCalendar('getDate');

    jQuery(".calendar-day-details").empty();
    jQuery(".day-details").empty();
    jQuery(".day-details").append("<div class='day-details'> "+ moment.format('dddd MMMM D, YYYY')+"</div>");

    jQuery.each(events, function (index, dateEvent) {

        var selectedDate = formatDate(dateEvent.start);

        if(selectedDate == now ){
            var dateH = "Due " +dateEvent.dueTime+"";
            if(dateEvent.reviewMode == false){
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details, .fc-content").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details, .fc-content").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }
            }else if(dateEvent.calLinkItem == true){
                var tag = dateEvent.title;
                var str = dateEvent.text;
                var title = "<a class='link-title' style='color: #0000ff' href=' "+dateEvent.textType+" '>"+dateEvent.linkTitle+"</a>";

                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details.fc-content").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+dropdown+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details, .fc-content").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.calLinkItemAvail == true){
                var tag = dateEvent.title;
                var str = dateEvent.text;

                var title = "<a class='link-title' style='color: #0000ff' href=' "+dateEvent.textType+" '>"+dateEvent.linkTitle+"</a>";

                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var html = '<span class="instronly common-setting">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+"'>Modify</a></li>"+
                        "</ul></span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.calLinkItemOnCal == true){
                var tag = dateEvent.title;
                var title = "<a class='link-title' style='color: #0000ff' href='../../course/course/show-linked-text?cid="+dateEvent.courseId+"&id="+dateEvent.linkedId+" '>"+dateEvent.linkTitle+"</a>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/link.png'/>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+dropdown+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            } else if(dateEvent.calInlineTextOncal == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                var title = dateEvent.name;
                var name = "<a href='../../course/course/course?cid="+dateEvent.courseId+"'>"+title+ "</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p>"+dropdown+"<br><p style='padding-left: 36px; '>"+name+"</p>/div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                }

            } else if(dateEvent.calInlineTextItem == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                var title = dateEvent.name;
                var name = "<a href='../../course/course/course?cid="+dateEvent.courseId+"'>"+title+ "</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p>"+dropdown+"<br><p style='padding-left: 36px;'>"+name+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+"<p style=' background-color: #FF6666; display: inline'>"+tag+"</p><br><p style='padding-left: 36px;'>"+name+"</p></div>");
                }
            } else if(dateEvent.calItem == true){
                var title = dateEvent.tagTitle;
                var date = dateEvent.start;
                var assessmentLogo = "<img alt='' class='floatleft item-icon-alignment' style='outline: none' src=''/>";
                var tag = dateEvent.title;
                jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br><b>"+tag+"</b><br/><p style='padding-left: 36px'>"+date+"</p></div>");
            } else if(dateEvent.reviewMode == true){
                var dateH = "Review until <b>" +dateEvent.reviewDat+"</b>";
                var reviewMode= "<p style='margin-left:35px!important;padding-top: 0'>This assessment is in review mode - no scores will be saved</p>";
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details, .fc-event-container").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"</div>");
                } else{
                    jQuery(".calendar-day-details, .fc-event-container").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"</div>");

                }
            } else if(dateEvent.reviewModeDueDate == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details, .fc-content, .fc-title").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details, .fc-content, .fc-title").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }else if(dateEvent.closeMode == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/iconAssessment.png'/>";
                var title = "<a class='' style='color: #0000ff;font-size: 16px' href='../../assessment/assessment/show-test?id="+dateEvent.assessmentId+"&cid="+dateEvent.courseId+" '>"+dateEvent.title+"</a>";
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../assessment/assessment/add-assessment?id="+selectedDate.assessmentId+"&cid="+selectedDate.courseId+" '>"+settings+ "</a></li>"+
                        "<li><a href='../../question/question/add-questions?cid="+selectedDate.courseId+"&aid="+selectedDate.assessmentId+" '>" +questions+ "</a></li>"+
                        "<li><a href='../../gradebook/gradebook/item-analysis?cid="+selectedDate.courseId+"&asid=average&aid="+selectedDate.assessmentId+" '>" +grades+ "</a></li>"+

                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details, .fc-content, .fc-title").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details, .fc-content, .fc-title").append("<div class='day-detail-border single-event'>"+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");

                }
            }
        }
    });
}


function getCurrentDate(){
    var now = new Date();
    var year = now.getFullYear();
    var month = now.getMonth()+1;
    var day = now.getDate();
    now = year+'-'+month+'-'+day;
    return now;
}

function formatDate(date){
    date = date.replace(/^0+/, '');
    var tempDate = date.split("-");
    var dateArray = [];
    jQuery.each(tempDate, function (index, tempString) {
        tempString = tempString.replace(/^0+/, '');
        dateArray.push(tempString);
    });
    var selectedDate = dateArray.toString();
    selectedDate = selectedDate.replace(',', '-');
    selectedDate = selectedDate.replace(',', '-');
    return selectedDate;
}