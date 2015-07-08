<?php
/* @var $this yii\web\View */
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>

    <title>OpenMath - OpenMathAdministration</title>
    <?php AppUtility::includeCSS('dashboard.css');?>
    <!-- DataTables CSS -->

<div class=mainbody>
<div class="headerwrapper"></div>
<div class="midwrapper">
    <div id="headeradmin" class="pagetitle"><h2>OpenMath Administration</h2></div>
    <h3>Courses</h3>

    <div class=item>
        <table id="course-table displayCourse" class="display course-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Course ID</th>
                <th>Owner</th>
                <th>Settings</th>
                <th>Teachers</th>
                <th>Transfer</th>
                <th>Delete</th>

            </tr>
            </thead>
            <tbody class="course-table-body">
            </tbody>
        </table>
        <div class="lg-col-2 pull-left">
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('course', 'course/add-new-course'); ?>">Add
            New Course</a>
        </div>
        <div class="lg-col-2 pull-left select-text-margin">
        &nbsp;&nbsp;Show courses of:&nbsp;&nbsp;
        </div>
        <div class="lg-col-3 pull-left">
            <select name="seluid" class="dropdown form-control" id="seluid" onchange="showcourses()">
                <option value="0" selected>Select a user..</option>
                <?php foreach ($users as $user) { ?>
                    <option
                        value="<?php echo $user['id'] ?>"><?php echo $user['FirstName'] . " " . $user['LastName'] . "(" . $user['SID'] . ")"; ?></option>
                <?php } ?>
            </select>
        </div>

    </div>


    <h3>Administration</h3>

    <div class=cp>
        <a HREF="<?php echo AppUtility::getURLFromHome('site', 'change-password') ?>">Change my password</a><BR>
        <a HREF="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Help</a><BR>
        <a HREF="<? echo AppUtility::getURLFromHome('site', 'work-in-progress')  ?>">Log Out</a><BR>
    </div>
    <div class=cp>
    <span class=column>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Manage Question Set</a><BR>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Export Question Set</a><BR>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Question Set</a><BR>
    </span>
    <span class=column>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Manage Libraries</a><br>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Export Libraries</a><BR>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Libraries</a></span>

    <span class=column>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Edit Groups</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Delete Old Users</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Students from File</a>
    </span>
    <span class="column"><a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Install Macro
            File</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Install Question Images</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Install Course Files</a><br/>
    </span>
    <span class="column"><a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">LTI Provider
            Creds</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">External Tools</a><br/>
        <a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Admin Utilities</a><br/>
    </span>

        <div class=clear></div>
    </div>

    <h3>Diagnostics</h3>

    <div class=item>
        <table class="gb" width="90%" id="diagTable">
            <thead>
            <tr>
                <th>Name</th>
                <th>Available</th>
                <th>Public</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <tr class=odd>

                <td><a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Check Here</a></td>
                <td class=c>Yes</td>
                <td class=c>Yes</td>
                <td><a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Modify</a></td>
                <td><a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Remove</a></td>
            <td><a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">One-time Passwords</a></td>
            </tr>
            </tbody>
        </table>

        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/admin-diagnostic') ?>">Add
            New
            Diagnostic</a>
    </div>

    <h3>Pending Users</h3>

    <div class=item>
        <table id="user-table displayCourse" class="display user-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Rights</th>
                <th>Last Login</th>
                <th>Rights</th>
                <th>Password</th>
                <th>Delete</th>
            </tr>
            </thead>
            <tbody class="user-table-body">

            </tbody>
        </table>
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/add-new-user') ?>">Add
            New User</a>
    </div>

    <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
<script type="text/javascript">
     $(document).ready(function () {

        jQuerySubmit('get-all-course-user-ajax',{},'getAllCourseSuccess');

    });

    function getAllCourseSuccess(response)
    {
        var courseData = JSON.parse(response);
        if(courseData.status == 0)
        {
            var courses = courseData.data.courses;
            var users = courseData.data.users;
            alert(JSON.stringify(courses));
            createCourseTable(courses);
            createUsersTable(users);
        }
    }

    function bindEvent(){
        //Show pop dialog for delete the course.
        $('.delete-link').click(function(e){
            e.preventDefault();
            var html = "<div>Are you sure to delete your course?</div>";
            var cancelUrl = $(this).attr('href');
            $('<div  id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Confirm": function () {
                        window.location = cancelUrl;
                        $(this).dialog("close");
                        return true;
                    },
                    "Cancel": function () {
                        $(this).dialog('destroy').remove();
                        return false;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        });

    }
    function createCourseTable(courses)
    {
        var html = "";
        $.each(courses, function(index, course){alert(course);
            html += "<tr> <td><a href='<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=')?>"+course.courseid+"'>"+capitalizeFirstLetter(course.name)+"</a></td>";
            html += "<td>"+course.courseid+"</td>";
            html += "<td>"+capitalizeFirstLetter(course.FirstName)+" "+capitalizeFirstLetter(course.LastName)+"</td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid=')?>"+course.courseid+"'>Setting</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid=') ?>"+course.courseid+"'>Add/Remove</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/transfer-course?cid=') ?>"+course.courseid+"'>Transfer</a></td>";
            html += "<td id='delete-link'><a class='delete-link' href='<?php echo AppUtility::getURLFromHome('course', 'course/delete-course?cid=') ?>"+course.courseid+"'>Delete</a></td></tr>";
        });
        $(".course-table-body").append(html);
        $('.course-table').DataTable();
        bindEvent();
    }

    function createUsersTable(users)
    { var html = "";
        $.each(users, function(index, users){
            html += "<tr> <td>"+capitalizeFirstLetter(users.FirstName)+" "+capitalizeFirstLetter(users.LastName)+"</td>";
            html += "<td>"+users.SID+"</td>";
            html += "<td>"+users.email+"</td>";
            html += "<td>"+users.rights+"</td>";
            html += "<td>"+users.lastaccess+"</td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('admin', 'admin/change-rights?id=')?>"+users.id+"'>Change</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>'"+users.id+"'>Reset</a></td>";
            html += "<td ><a href='<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>'"+users.id+"'>Delete</a></td></tr>";
        });
        $(".user-table-body").append(html);
        $('.user-table').DataTable();
    }
</script>