$(document).ready(function(){

    //checkbox
    $("#coursesettingform-available :checkbox").prop('checked', true);
    $("#coursesettingform-navigationlink :checkbox").prop('checked', true);
    $("#coursesettingform-coursereordering :checkbox").prop('checked', true);

    //icon
    $("#coursesettingform-icons :radio:first").prop('checked', true);

    //show icon
    $(".radio-assesments label input").first().prop('checked', true);
    $(".radio-inline label input").first().prop('checked', true);

    //self-unenroll
    $("#coursesettingform-selfunenroll :radio:first").prop('checked', true);

    //self-enroll
    $("#coursesettingform-selfenroll :radio:last").prop('checked', true);

    //copy-course-item
    $("#coursesettingform-copycourse :radio:first").prop('checked', true);

    //msg-system
    $("#coursesettingform-messagesystem :radio:first").prop('checked', true);
})