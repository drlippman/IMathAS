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
//    $(".radio-linked :radio:last").prop('checked', true);
//    $(".radio-forums :radio:first").prop('checked', true);
//    $(".radio-blocks :radio:last").prop('checked', true);

    //selfunenroll
    $("#coursesettingform-selfunenroll :radio:first").prop('checked', true);

    //selfenroll
    $("#coursesettingform-selfenroll :radio:last").prop('checked', true);

    //copycourseitem
    $("#coursesettingform-copycourse :radio:first").prop('checked', true);

    //msgsystem
    $("#coursesettingform-messagesystem :radio:first").prop('checked', true);
})