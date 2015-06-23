//$(document).ready(function () {
//
//    jQuerySubmit('get-all-course-user-ajax',{},'getAllCourseSuccess');
//
//});
//
//function getAllCourseSuccess(response)
//{
//    response = JSON.parse(response);
//    if(response.status == 0)
//    {
//        var courses = response.data.courses;
//        var users = response.data.users;
//        createCourseTable(courses);
//        createUsersTable(users);
//    }
//}
//
//function bindEvent(){
//    //Show pop dialog for delete the course.
//    $('.delete-link').click(function(e){
//        e.preventDefault();
//        var html = "<div>Are you sure to delete your course?</div>";
//        var cancelUrl = $(this).attr('href');
//        $('<div  id="dialog"></div>').appendTo('body').html(html).dialog({
//            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
//            width: 'auto', resizable: false,
//            closeText: "hide",
//            buttons: {
//                "Confirm": function () {
//                    window.location = cancelUrl;
//                    $(this).dialog("close");
//                    return true;
//                },
//                "Cancel": function () {
//                    $(this).dialog('destroy').remove();
//                    return false;
//                }
//            },
//            close: function (event, ui) {
//                $(this).remove();
//            }
//        });
//    });
//
//}
//function createCourseTable(courses)
//{
//    var html = "";
//    $.each(courses, function(index, course){
//        html += "<tr> <td><a href='#'>"+capitalizeFirstLetter(course.name)+"</a></td>";
//        html += "<td>"+course.courseid+"</td>";
//        html += "<td>"+capitalizeFirstLetter(course.FirstName)+" "+capitalizeFirstLetter(course.LastName)+"</td>";
//        html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid=')?>"+course.courseid+"'>Setting</a></td>";
//        html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid=') ?>"+course.courseid+"'>Add/Remove</a></td>";
//        html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/transfer-course?cid=') ?>"+course.courseid+"'>Transfer</a></td>";
//        html += "<td id='delete-link'><a class='delete-link' href='<?php echo AppUtility::getURLFromHome('course', 'course/delete-course?cid=') ?>"+course.courseid+"'>Delete</a></td></tr>";
//    });
//    $(".course-table-body").append(html);
//    $('.course-table').DataTable();
//    bindEvent();
//}
//
//
//function createUsersTable(users)
//{ var html = "";
//    $.each(users, function(index, users){
//        html += "<tr> <td>"+capitalizeFirstLetter(users.FirstName)+" "+capitalizeFirstLetter(users.LastName)+"</td>";
//        html += "<td>"+users.SID+"</td>";
//        html += "<td>"+users.email+"</td>";
//        html += "<td>"+users.rights+"</td>";
//        html += "<td>"+users.lastaccess+"</td>";
//        html += "<td><a href='<?php echo AppUtility::getURLFromHome('admin', 'admin/change-rights?id=')?>"+users.id+"'>Change</a></td>";
//        html += "<td><a href='<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>'"+users.id+"'>Reset</a></td>";
//        html += "<td ><a href='<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>'"+users.id+"'>Delete</a></td></tr>";
//    });
//    $(".user-table-body").append(html);
//    $('.user-table').DataTable();
//}
