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
                        if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > assessmentDetail.now)
                        {
                            eventColor = 'red';
                        } else  if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString == 2000000000)
                        {
                            eventColor = 'red';
                        }
                        /**
                         * If assessment is in review mode, event
                         */
                        if(assessmentDetail.endDateString < assessmentDetail.now && assessmentDetail.reviewDateString != 2000000000 && assessmentDetail.reviewDateString > assessmentDetail.now)
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
                        if(calendarLinkItem.oncal == 2){
                            if(calendarLinkItem.startDateString < calendarLinkItem.now && calendarLinkItem.endDateString > calendarLinkItem.now)
                             {
                                events.push({
                                    title: calendarLinkItem.calTag,
                                    linkTitle: calendarLinkItem.title,
                                    start: calendarLinkItem.endDate,
                                    linkedId: calendarLinkItem.linkedId,
                                    dueTime: calendarLinkItem.dueTime,
                                    id: calendarLinkItem.id,
                                    oncal:calendarLinkItem.oncal,
                                    color: eventColor,
                                    courseId: calendarLinkItem.courseId,
                                    message: 'Linked text events',
                                    calLinkItemOnCal: true

                                });
                            }
                        } else if(calendarLinkItem.oncal == 1){
                            events.push({
                                title: calendarLinkItem.calTag,
                                linkTitle: calendarLinkItem.title,
                                start: calendarLinkItem.startDate,
                                linkedId: calendarLinkItem.linkedId,
                                dueTime: calendarLinkItem.dueTime,
                                id: calendarLinkItem.id,
                                oncal:calendarLinkItem.oncal,
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
                    jQuery.each(assessmentData.calendarInlineTextArray, function (index, calendarInlineTextItem) {
                        var eventColor = '#FF6666';
                        if(calendarInlineTextItem.oncal == 2){
                            if(calendarInlineTextItem.startDateString < calendarInlineTextItem.now && calendarInlineTextItem.endDateString > calendarInlineTextItem.now)
                            {
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.endDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextOncal: true
                                });
                            }
                        } else if(calendarInlineTextItem.oncal == 1){

                            if(calendarInlineTextItem.startDateString < calendarInlineTextItem.now && calendarInlineTextItem.endDateString > calendarInlineTextItem.now)
                            {
                                events.push({
                                    title: calendarInlineTextItem.calTag,
                                    start: calendarInlineTextItem.startDate,
                                    dueTime: calendarInlineTextItem.dueTime,
                                    courseId: calendarInlineTextItem.courseId,
                                    id: calendarInlineTextItem.id,
                                    oncal:calendarInlineTextItem.oncal,
                                    color: eventColor,
                                    message: 'Inline text events',
                                    calInlineTextItem: true
                                });
                            }
                        }

                    });
                    calendarEvents.push(events);
                    displayCalEvents(events);
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
            jQuery(".calendar-day-details").empty();
            jQuery.each(calendarEvents[0], function (index, selectedDate) {
                if(selectedDate.start == selectedDay) {
                    if(currentDate == formattedSelectedDay){
                        var dateH = "Due " +selectedDate.dueTime+"";
                    }else{
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
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }
                    }  else if(selectedDate.calLinkItem == true){
                        var tag = selectedDate.title;
                        var title = "<a class='link-title' style='color: #0000ff' href='../../course/course/show-linked-text?cid="+selectedDate.courseId+"&id="+selectedDate.linkedId+" '>"+selectedDate.linkTitle+"</a>";
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
                    } else if(selectedDate.calLinkItemOnCal == true){
                        var tag = selectedDate.title;
                        var title = "<a class='link-title' style='color: #0000ff' href='../../course/course/show-linked-text?cid="+selectedDate.courseId+"&id="+selectedDate.linkedId+" '>"+selectedDate.linkTitle+"</a>";
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
                        var title = "<a class='link-title'style='color: #0000ff' href='../../course/course/show-linked-text?cid="+selectedDate.courseId+"&id="+selectedDate.linkedId+" '>"+selectedDate.linkTitle+"</a>";
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/add-link?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+tag+"</p><br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }
                    } else if(selectedDate.calLinkItemOnCal == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                        var title = "<a class='link-title' style='color: #0000ff' href='../../course/course/show-linked-text?cid="+selectedDate.courseId+"&id="+selectedDate.linkedId+" '>"+selectedDate.linkTitle+"</a>";
                        var tag = selectedDate.title;
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/add-link?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+html+"<br><p style='padding-left: 36px'>"+tag+"</p><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br><p style='padding-left: 36px'>"+tag+"</p><br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }
                    } else if(selectedDate.calInlineTextItem == true){
                        var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                        var tag = selectedDate.title;
                        if(userRights > 10){
                            var html = '<span class="instronly common-setting">'+
                                '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                                '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                                '<ul class="select1 dropdown-menu selected-options pull-right">' +
                                "<li><a href='../../course/course/modify-inline-text?cid="+selectedDate.courseId+"&id="+selectedDate.id+" '>"+modify+"</a></li>"+
                                "</ul></span>";
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+html+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                        }
                    } else if(selectedDate.calItem == true){
                        var title = selectedDate.tagTitle;
                        var assessmentLogo = "<img alt='' class='floatleft item-icon-alignment' style='outline: none' src=''/>";
                        var tag = selectedDate.title;
                        jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+"<br>"+title+"<br><p style='padding-left: 36px'></p></div>");
                    } else if(selectedDate.reviewMode == true){
                        var dateH = "Review until <b>" +selectedDate.reviewDat+"</b>";
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
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+selectedDate.title+" "+dropdown+"</b><br>"+dateH+"."+reviewMode+"</div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+selectedDate.title+"</b><br>"+dateH+"."+reviewMode+"</div>");
                        }
                    } else if(selectedDate.reviewModeDueDate == true){
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
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+selectedDate.title+" "+dropdown+"</b><br>"+dateH+"</div>");
                        } else{
                            jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+selectedDate.title+"</b><br>"+dateH+"</div>");
                        }
                    }
                }
            });
        }
    });

}
function displayCalEvents(events) {
    var now = getCurrentDate();
    var userRights = jQuery('.user-rights').val();
    var settings = 'Settings';
    var questions = 'Questions';
    var grades = 'Grades';
    var modify = 'Modify';

    jQuery(".calendar-day-details").empty();
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
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+" "+title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }
            }else if(dateEvent.calLinkItem == true){
                var tag = dateEvent.title;
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
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/add-link?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }

            } else if(dateEvent.calInlineTextItem == true){
                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+
                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }
            } else if(dateEvent.calInlineTextItem == true){

                var assessmentLogo = "<img alt='assess' class='floatleft item-icon-alignment' src='../../img/inlineText.png'/>";
                var tag = dateEvent.title;
                if(userRights > 10){
                    var dropdown = '<span class="instronly common-setting-calendar">'+
                        '<a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);">' +
                        '<img alt="setting" class="floatright course-setting-button" src="../../img/courseSettingItem.png"/></a>'+
                        '<ul class="select1 dropdown-menu selected-options pull-right">' +
                        "<li><a href='../../course/course/modify-inline-text?cid="+dateEvent.courseId+"&id="+dateEvent.id+" '>"+modify+ "</a></li>"+


                        "</ul>"+
                        "</span>";
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+tag+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                }
            } else if(dateEvent.calItem == true){
                var title = dateEvent.tagTitle;
                var assessmentLogo = "<img alt='' class='floatleft item-icon-alignment' style='outline: none' src=''/>";
                var tag = dateEvent.title;
                jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'> "+assessmentLogo+title+"<br>"+tag+"<br><p style='padding-left: 36px'></p></div>");
            } else if(dateEvent.reviewMode == true){
                var dateH = "Review until <b>" +dateEvent.reviewDat+"</b>";
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
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+dateEvent.title+" "+dropdown+"</div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+dateEvent.title+"</div>");

                }
            } else if(dateEvent.reviewModeDueDate == true){
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
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+dateEvent.title+" "+dropdown+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");
                } else{
                    jQuery(".calendar-day-details").append("<div class='day-detail-border single-event'>"+assessmentLogo+"<b> "+dateEvent.title+"<br><p style='padding-left: 36px'>"+dateH+"</p></div>");

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