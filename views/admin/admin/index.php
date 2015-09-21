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
                <th style="max-width: 400px">Name</th>
                <th STYLE="text-align: center">Course ID</th>
                <th >Owner</th>
                <th STYLE="text-align: center" >Settings</th>

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
                <td style="text-align: center">
                    <div class='btn-group settings'> <a class='btn btn-primary setting-btn' href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modify&cid='.$page_courseList[$i]['id']);?>">
                        <i class='fa fa-cog fa-fw'></i> Settings</a><a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>
                    <ul class='dropdown-menu'>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid='.$page_courseList[$i]['id']);?>"><i class="fa fa-pencil"></i>&nbsp;Add/Remove</a>
                        </li>
                        <li>
                            <a  href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=transfer&cid='.$page_courseList[$i]['id']);?>"><i class="fa fa-exchange"></i>&nbsp;Transfer</a>
                        </li>
                        <li>
                            <a href='<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=delete&id='.$page_courseList[$i]['id']) ?>'><i class='fa fa-trash-o'></i></i>&nbsp;Delete</a>
                        </li>
                    </ul></div>
                </td>
            <?php
            }
            ?>
            </tbody>
        </table>
        <div class="lg-col-2 pull-left">
        <a class="btn btn-primary margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=addcourse') ?>">Add
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
            <a href="<?php echo AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=admin') ?>">Manage Question Set</a><BR>

            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/export-question-set?cid=admin') ?>">Export Question Set</a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/import-question-set?cid=admin') ?>">Import Question Set</a><BR>

        </span>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/manage-lib?cid=admin') ?>">Manage Libraries</a><br>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/export-lib?cid=admin') ?>">Export Libraries</a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/import-lib?cid=admin') ?>">Import Libraries</a>
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
                <th style="text-align: center">Modify</th>
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
                <td style="text-align: center">
                    <div class='btn-group settings'> <a class='btn btn-primary setting-btn' href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics?id='.$page_diagnosticsId[$i])?>">
                            <i class="fa fa-pencil"></i> Modify</a><a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>
                        <ul class='dropdown-menu'>
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=removediag&id='.$page_diagnosticsId[$i]);?>"><i class='fa fa-trash-o'></i>&nbsp;Remove</a>
                            </li>
                            <li>
                                <a  href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id='.$page_diagnosticsId[$i])?>"><i class="fa fa-key"></i>&nbsp;One-time Passwords</a>
                            </li>
                        </ul></div>
                </td>
            <?php
            }
            ?>
            </tbody>
        </table>

        <a class="btn btn-primary margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics') ?>">Add
            New Diagnostic</a>
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
                <td class=c><a href="<?php echo AppUtility::getURLFromHome('site', 'change-password?id='.$page_userDataId[$i]) ?>">Reset</a></td>
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
     $(document).ready(function ()
     {
         $('.course-table').DataTable();
    });
     function showgroupusers() {
         var grpid=document.getElementById("selgrpid").value;
         window.location= 'index?showusers='+grpid;
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

     function showcourses() {
           var uid = $("#showCid ").val();
         alert(uid);

           if (uid>0) {
               window.location='index?showcourses='+uid;
           }
     }
</script>