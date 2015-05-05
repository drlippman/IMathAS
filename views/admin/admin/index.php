<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <title>OpenMath - OpenMathAdministration</title>
    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css"
          href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
</head>
<body>
<div class=mainbody>
<div class="headerwrapper"></div>
<div class="midwrapper">
    <div id="headeradmin" class="pagetitle"><h2>OpenMath Administration</h2></div>
    <h3>Courses</h3>

    <div class=item>
        <table id="course-table displayCourse" class="display course-table">
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
        <table class=gb width="90%" id="diagTable">
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
                <td><a href="<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Modify</a></td>
                <td><a href=<? echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Remove</a></td>
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
        <table id="user-table" class="display">
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
            <tbody>
            <?php
            foreach ($users as $key => $user) {
                if ($user->rights == 0) {
                    $even = 'even';
                    $odd = 'odd'; ?>
                    <tr class="<?php echo (($key % 2) != 0) ? 'even' : 'odd'; ?>">

                        <td>
                            <?php echo(ucfirst($user->FirstName)); ?>
                            &nbsp;&nbsp;<?php echo(ucfirst($user->LastName)); ?>
                        </td>

                        <td>
                            <?php echo $user->SID; ?>
                        </td>
                        <td>
                            <?php echo $user->email; ?>
                        </td>
                        <td>
                            <?php echo \app\components\AppUtility::getRight($user->rights); ?>
                        </td>
                        <td>
                            <?php echo $user->lastaccess; ?>
                        </td>
                        <td>
                            <a href="#"><?php echo 'Change'; ?></a>
                        </td>
                        <td>
                            <a href="#"><?php echo 'Reset'; ?></a>
                        </td>
                        <td>
                            <a href="#"><?php echo 'Delete'; ?></a>
                        </td>
                    </tr>
                <?php
                }
            }
            ?>
            </tbody>
        </table>
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/add-new-user') ?>">Add
            New User</a>
    </div>

    <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
</body>
</html>
<script type="text/javascript">
    $(document).ready(function () {
        $.ajax({
            type: "POST",
            url: "get-all-course-ajax",
            data:{
            },
            success: function (response){
                console.log(response);
                var result = JSON.parse(response);
                if(result.status == 0)
                {
                    var courses = result.courses;
                    createCourseTable(courses);
                }
            },
            error: function(xhRequest, ErrorText, thrownError) {
                console.log(ErrorText);
            }
        });



//Show pop dialog for delete the course.
        $(".deleteCourse").on("click", function (e) {
            var html = "<div>Are you sure to delete your course?</div>";
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div></div>').appendTo('body').html(html).dialog({
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
                        $(this).dialog("close");
                        return false;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        });

    });

    function createCourseTable(courses)
    {
        var html = "";
        $.each(courses, function(index, course){
            html += "<tr> <td><a href='#'>"+capitalizeFirstLetter(course.name)+"</a></td>";
            html += "<td>"+course.courseid+"</td>";
            html += "<td>"+capitalizeFirstLetter(course.FirstName)+" "+capitalizeFirstLetter(course.LastName)+"</td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid=')?>"+course.courseid+"'>Setting</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid=') ?>"+course.courseid+"'>Add/Remove</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/transfer-course?cid=') ?>"+course.courseid+"'>Transfer</a></td>";
            html += "<td><a href='<?php echo AppUtility::getURLFromHome('course', 'course/delete-course?cid=') ?>"+course.courseid+"'>Delete</a></td></tr>";
        });
        $(".course-table-body").append(html);
        $('.course-table').DataTable();
        $('#user-table').DataTable();
    }

    function isElementExist(element)
    {
        if ($(element).length){
            return true;
        }
        return false;
    }

    function capitalizeFirstLetter(str)
    {
        return str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });

    }






</script>