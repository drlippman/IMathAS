<?php
/* @var $this yii\web\View */
$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <title>OpenMath - OpenMathAdministration</title>
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::$app->homeUrl ?>css/dashboard.css"/>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::$app->homeUrl ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">

    <script type="text/javascript" src="<?php echo Yii::$app->homeUrl ?>js/general.js?ver=012115"></script>
    <!-- jQuery -->
    <script type="text/javascript" charset="utf8" src="<?php echo Yii::$app->homeUrl ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>

    <!-- DataTables -->
    <script type="text/javascript" charset="utf8" src="<?php echo Yii::$app->homeUrl ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
</head>
<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',0)" onmouseout="mclosetime()"></div>
        <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>

        <div id="headeradmin" class="pagetitle"><h2>OpenMath Administration</h2></div>
        <h3>Courses</h3>

        <div class=item>


            <table id="course_table" class="display">
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
                <tbody>
                <?php
                foreach ($courseData as $key => $course) {
                    $even = 'even';
                    $odd = 'odd'; ?>

                    <tr class="<?php echo (($key % 2) != 0) ? 'even' : 'odd'; ?>">
                        <td>
                            <a href="#"><?php echo ucfirst($course->name); ?></a>
                        </td>
                        <td>
                            <?php echo $course->id; ?>
                        </td>

                        <td>
                            <?php echo(ucfirst($course->owner->FirstName)); ?>
                            &nbsp;&nbsp;<?php echo(ucfirst($course->owner->LastName)); ?>
                        </td>

                        <td>
                            <a href=""><?php echo 'Setting'; ?></a>
                        </td>

                        <td>
                            <a href=""><?php echo 'Add/Remove'; ?></a>
                        </td>

                        <td>
                            <a href=""><?php echo 'Transfer'; ?></a>
                        </td>

                        <td>
                            <a href=""><?php echo 'Delete'; ?></a>
                        </td>
                    </tr>


                <?php
                }
                ?>
                </tbody>
            </table>

            <input type=button class="btn btn-primary" value="Add New Course"
                   onclick="window.location='../site/course-setting'"/>
            Show courses of: <select name="seluid" class="dropdown" id="seluid" onchange="showcourses()">
                <option value="0" selected>Select a user..</option>
                <?php foreach ($users as $user) { ?>
                    <option
                        value="<?php echo $user['id'] ?>"><?php echo $user['FirstName'] . " " . $user['LastName'] . "(" . $user['SID'] . ")"; ?></option>
                <?php } ?>
            </select>

        </div>


        <h3>Administration</h3>

        <div class=cp>
            <A HREF="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Change my password</a><BR>
            <A HREF="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Help</a><BR>
            <A HREF="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Log Out</a><BR>
        </div>
        <div class=cp>
            <span class=column>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Manage Question Set</a><BR>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Export Question Set</a><BR>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Import Question Set</a><BR>
            </span>
            <span class=column>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Manage Libraries</a><br>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Export Libraries</a><BR>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Import Libraries</a></span>

            <span class=column>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Edit Groups</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Delete Old Users</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Import Students from File</a>
            </span>
            <span class="column"><a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Install Macro
                    File</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Install Question Images</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Install Course Files</a><br/>
            </span>
            <span class="column"><a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">LTI Provider
                    Creds</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">External Tools</a><br/>
                <a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Admin Utilities</a><br/>
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

                    <td><a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Check Here</a></td>
                    <td class=c>Yes</td>
                    <td class=c>Yes</td>
                    <td><a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Modify</a></td>
                    <td><a href=<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">Remove</a></td>
                    <td><a href="<? echo(Yii::$app->homeUrl) ?>site/work-in-progress">One-time Passwords</a></td>
                </tr>
                </tbody>
            </table>

            <input type=button class="btn btn-primary" value="Add New Diagnostic">
        </div>

        <h3>Pending Users</h3>

        <div class=item>
            <table id="user_table" class="display">
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
                    if($user->rights == 0)
                    {
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
                                <a href="#"><?php echo 'Change';?></a>
                            </td>
                            <td>
                                <a href="#"><?php echo 'Reset';?></a>
                            </td>
                            <td>
                                <a href="#"><?php echo 'Delete';?></a>
                            </td>
                        </tr>
                    <?php
                    }
                }
                ?>
                </tbody>
            </table>

            <input type=button class="btn btn-primary" value="Add New User"
                   onclick="window.location='<? echo(Yii::$app->homeUrl) ?>site/add-new-user'">
        </div>

        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
</html>
<script type="text/javascript">
    $(document).ready( function () {
        $('#course_table').DataTable();
        $('#user_table').DataTable();
    } );
</script>