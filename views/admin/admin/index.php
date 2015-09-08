<?php
/* @var $this yii\web\View */
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'OpenMath Administration';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
       <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]);?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
    <?php AppUtility::includeCSS('dashboard.css');?>
    <!-- DataTables CSS -->

<div class=mainbody>
<div class="headerwrapper"></div>
<input type="hidden" id="showCid" value="<?php echo $showCid;?>">
<div class="midwrapper">
<div class="tab-content shadowBox non-nav-tab-item">
<br>
    <div class="col-lg-12"><b>Hello <?php echo $userName ?></b></div>
    <div class="col-lg-12 margin-left-five"><h3>Courses</h3></div>

    <div class='item margin-padding-admin-table padding-bottom'>

        <table class="display course-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th STYLE="text-align: center">Course ID</th>
                <th >Owner</th>
                <th STYLE="text-align: center" >Settings</th>
                <th STYLE="text-align: center">Teachers</th>
                <th STYLE="text-align: center">Transfer</th>
                <th STYLE="text-align: center">Delete</th>

            </tr>
            </thead>
            <tbody>
            <?php
            $alt = AppConstant::NUMERIC_ZERO;
            for ($i=0;$i<count($page_courseList);$i++) {

                if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
                ?>
                <td><a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid='.$page_courseList[$i]['id'])?>">
                        <?php
                        if (($page_courseList[$i]['available']&1)==1) {
                            echo '<i>';
                        }
                        if (($page_courseList[$i]['available']&2)==2) {
                            echo '<span style="color:#aaf;">';
                        }
                        if (($page_courseList[$i]['available']&4)==4) {
                            echo '<span style="color:#faa;text-decoration: line-through;">';
                        }

                        echo $page_courseList[$i]['name'];

                        if (($page_courseList[$i]['available']&1)==1) {
                            echo '</i>';
                        }
                        if (($page_courseList[$i]['available']&2)==2 || ($page_courseList[$i]['available']&4)==4) {
                            echo '</span>';
                        }

                        ?>
                    </a>
                </td>
                <td class=c><?php echo $page_courseList[$i]['id'] ?></td>
                <td><?php echo $page_courseList[$i]['LastName'] ?>, <?php echo $page_courseList[$i]['FirstName'] ?></td>
                <td class=c><a href="<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid='.$page_courseList[$i]['id']);?>">Settings</a></td>
                <td class=c><a href="<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid='.$page_courseList[$i]['id']);?>">Add/Remove</a></td>
                <td class=c><a href="<?php echo AppUtility::getURLFromHome('course', 'course/transfer-course?cid='.$page_courseList[$i]['id']);?>">Transfer</a></td>
                <td class=c><a class='delete-link' href='<?php echo AppUtility::getURLFromHome('course', 'course/delete-course?cid='.$page_courseList[$i]['id']) ?>"'>Delete</a></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
        <div class="lg-col-2 pull-left">
        <a class="btn btn-primary margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('course', 'course/add-new-course'); ?>">Add
            New Course</a>
        </div>

<!--        <div class="lg-col-2 pull-left select-text-margin">-->
<!--            &nbsp;&nbsp;Show courses of:&nbsp;&nbsp;-->
<!--        </div>-->
<!---->
<!--        <div class="lg-col-3 pull-left">-->
<!--            <select name="seluid" class="dropdown form-control" id="seluid" onchange="showcourses()">-->
<!--                <option value="0" selected>Select a user..</option>-->
<!--                --><?php // $i=0;
//                foreach($resultTeacher as $key => $row)
//                {  $page_teacherSelectVal[$i] = $row['id'];?>
<!--                    <option-->
<!--                        value="--><?php //echo $page_teacherSelectLabel[$i] ?><!--">--><?php //echo $row['LastName'] . ", " . $row['FirstName']. ' ('.$row['SID'].')'; $i++;?><!--</option>-->
<!--                --><?php //} ?>
<!--            </select>-->
<!--        </div>-->

        <?php
        if ($myRights >= 75) {
            if ($showcourses > 0) {
                echo "<input type=button value=\"Show My Courses\" onclick=\"window.location='index?showcourses=0'\" />";
            }

            echo "<div class='col-lg-3'>";
            AppUtility::writeHtmlSelect ("seluid",$page_teacherSelectVal,$page_teacherSelectLabel,$showcourses,"Select a user..",0,"onchange=\"showcourses()\"");?></div>
     <?php   }
        ?>
    </div>


    <div class="col-lg-12 margin-left-five"><h3>Administration</h3></div>

        <div class='cp item margin-left-twenty'>
            <span class=column><a HREF="<?php echo AppUtility::getURLFromHome('site', 'change-password') ?>">Change my password</a></span>
            <span class=column><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Help</a></span>
            <span class=column><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress')  ?>">Log Out</a></span><BR>
        </div>
    <?php
    if($myRights < 75 && isset($CFG['GEN']['allowteacherexport'])) {
    ?>
    <div class=cp>
        <a href="#">Export Question Set</a><BR>
        <a href="#">Export Libraries</a>
    </div>
    <?php
    } else if($myRights >= 75) {
    ?>
    <div class='cp item margin-left-twenty padding-bottom'>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Manage Question Set</a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Export Question Set</a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Question Set</a><BR>
        </span>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/manage-lib?cid=admin') ?>">Manage Libraries</a><br>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Export Libraries</a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Libraries</a>
        </span>
        <?php
        if ($myRights == 100) {
        ?>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=listgroups') ?>">Edit Groups</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=deloldusers') ?>">Delete Old Users</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>">Import Students from File</a>
        </span>
        <span class="column">
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importmacros') ?>">Install Macro File</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importqimages') ?>">Install Question Images</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importcoursefiles') ?>">Install Course Files</a><br/>
        </span>
        <span class="column">
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=listltidomaincred') ?>">LTI Provider Creds</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/external-tool?cid=admin') ?>">External Tools</a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities') ?>">Admin Utilities</a><br/>
        </span>
        <?php
        }
        ?>
        <div class=clear></div>
    </div>
    <?php
    }
    if($myRights >= 60) {?>
    <div class="col-lg-12 margin-left-five"><h3>Diagnostics</h3></div>

    <div class='item margin-padding-admin-table padding-bottom'>
        <table class="display course-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th STYLE="text-align: center">Available</th>
                <th STYLE="text-align: center">Public</th>
                <th>Modify</th>
                <th>Remove</th>
                <th>One-time Passwords</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alt = 0;
            for ($i=0;$i<count($page_diagnosticsId);$i++) {
                if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
                ?>
                <td><a href="<?php echo AppUtility::getURLFromHome('site', 'diagnostics?id='.$page_diagnosticsId[$i])?>"><?php echo $page_diagnosticsName[$i] ?></a></td>
                <td class=c><?php echo $page_diagnosticsAvailable[$i] ?></td>
                <td class=c><?php echo $page_diagnosticsPublic[$i] ?></td>
                <td><a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics?id='.$page_diagnosticsId[$i])?>">Modify</a></td>
                <td><a href="#?id=<?php echo $page_diagnosticsId[$i] ?>">Remove</a></td>
                <td><a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id='.$page_diagnosticsId[$i])?>">One-time Passwords</a></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>

        <a class="btn btn-primary margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics') ?>">Add
            New
            Diagnostic</a>
    </div>
<?php } if($myRights >= 75) {?>
    <div class="col-lg-12 margin-left-five"><h3><?php echo $page_userBlockTitle?></h3></div>

    <div class='item margin-padding-admin-table padding-bottom'>
        <table class="display course-table table table-bordered table-striped table-hover data-table">
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
            <tbody class="">

            <?php
            for ($i=0;$i<count($page_userDataId);$i++) {
                if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
                ?>
                <td><?php echo $page_userDataLastName[$i] . ", " . $page_userDataFirstName[$i] ?></td>
                <td><?php echo $page_userDataSid[$i] ?></td>
                <td><?php echo $page_userDataEmail[$i] ?></td>
                <td><?php echo $page_userDataType[$i] ?></td>
                <td><?php echo $page_userDataLastAccess[$i] ?></td>
                <td class=c><a href=<?php echo AppUtility::getURLFromHome('admin', 'admin/change-rights?id='.$page_userDataId[$i])?>>Change</a></td>
                <td class=c><a href="#">Reset</a></td>
                <td class=c><a href="#">Delete</a></td>
                </tr>
            <?php
            }
                     ?>

            </tbody>
        </table>
        <a class="btn btn-primary margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/add-new-user') ?>">Add
            New User</a>
         <?php  }
         if ($myRights == 100) {
            writeHtmlSelect ("selgrpid",$page_userSelectVal,$page_userSelectLabel,$showusers,null,null,"onchange=\"showgroupusers()\"");
        }
        ?>
        <?php
        function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
        echo "<select class='user-select' name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "" ;
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
        echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i=0;$i<count($valList);$i++) {
        if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
        echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
        } else {
        echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
        }
        }
        echo "</select>\n";
        }
        ?>

    </div>

    <div class="clear"></div>
</div>
</div>
</div>
<script type="text/javascript">
     $(document).ready(function () {
        jQuerySubmit('get-all-course-user-ajax',{},'getAllCourseSuccess');
    });
     function showgroupusers() {
         var grpid=document.getElementById("selgrpid").value;
         window.location= 'index?showusers='+grpid;
         }
    function getAllCourseSuccess(response)
    {
        var courseData = JSON.parse(response);
        if(courseData.status == 0)
        {
            var courses = courseData.data.courses;
            var users = courseData.data.users;
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
        $.each(courses, function(index, course){
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
     function showcourses() {
           var uid = $("#showCid ").val();
         alert(uid);

           if (uid>0) {
               window.location='index?showcourses='+uid;
           }
     }
</script>