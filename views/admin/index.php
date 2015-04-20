<?php
/* @var $this yii\web\View */
$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <title>OpenMath - OpenMathAdministration</title>
    <link rel="stylesheet" type="text/css" href="../../web/css/dashboard.css" />
    <script type="text/javascript" src="../../web/js/general.js?ver=012115"></script>
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
            <table class=gb border=0 width="100%">
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
                <tr class=even>

                </tr>
                <tr class=odd>

                </tr>
                </tbody>
            </table>
            <input type=button class="btn btn-primary" value="Add New Course" onclick="window.location='forms.php?action=addcourse'"/>
            Show courses of: <select name="seluid" class="dropdown" id="seluid" onchange="showcourses()">
                <option value="0" selected>Select a user..</option>
            </select>
        </div>


        <h3>Administration</h3>

        <div class=cp>
            <A HREF="work-in-progress">Change my password</a><BR>
            <A HREF="work-in-progress">Help</a><BR>
            <A HREF="work-in-progress">Log Out</a><BR>
        </div>
        <div class=cp>
	<span class=column>
	<a href="work-in-progress">Manage Question Set</a><BR>
	<a href="work-in-progress">Export Question Set</a><BR>
	<a href="work-in-progress">Import Question Set</a><BR>
	</span>
	<span class=column>
	<a href="work-in-progress">Manage Libraries</a><br>
	<a href="work-in-progress">Export Libraries</a><BR>
	<a href="work-in-progress">Import Libraries</a></span>

	<span class=column>
	<a href="work-in-progress">Edit Groups</a><br/>
	<a href="work-in-progress">Delete Old Users</a><br/>
	<a href="work-in-progress">Import Students from File</a>
	</span>
	<span class="column"><a href="work-in-progress">Install Macro File</a><br/>
<a href="work-in-progress">Install Question Images</a><br/>
<a href="work-in-progress">Install Course Files</a><br/>
</span><span class="column"><a href="work-in-progress">LTI Provider Creds</a><br/>
<a href="work-in-progress">External Tools</a><br/>
<a href="work-in-progress">Admin Utilities</a><br/>
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

                    <td><a href="work-in-progress">Check Here</a></td>
                    <td class=c>Yes</td>
                    <td class=c>Yes</td>
                    <td><a href="work-in-progress">Modify</a></td>
                    <td><a href=work-in-progress">Remove</a></td>
                    <td><a href="work-in-progress">One-time Passwords</a></td>
                </tr>
                </tbody>
            </table>
            <input type=button class="btn btn-primary" value="Add New Diagnostic" onclick="window.location='/IMathAS/admin/diagsetup.php'">
        </div>

        <h3>Pending Users</h3>

        <div class=item>
            <table class=gb width="100%" id="myTable">
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
                <tr class=even>

                </tr>
                <tr class=odd>

                </tr>
                </tbody>
            </table>

            <input type=button class="btn btn-primary" value="Add New User" onclick="window.location='forms.php?action=newadmin'">
        </div>

        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
</html>