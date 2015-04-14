<?php
/* @var $this yii\web\View */
$this->title = 'OpenMath';
?>
<!DOCTYPE html>
<html>
<head>
    <title>IMathAS - IMathASAdministration</title>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../../web/js/jquery.min.js" type="text/javascript"></script>
    <script src="../../web/js/dashboard.js" type="text/javascript"></script>

    <link rel="stylesheet" href="../../web/css/imascore.css?ver=030415" type="text/css"/>
    <link rel="stylesheet" href="../../web/css/default.css?v=121713" type="text/css"/>
    <link rel="stylesheet" href="../../web/css/handheld.css" media="handheld,only screen and (max-device-width:480px)"/>

    <link rel="shortcut icon" href="/favicon.ico"/>
    <link rel="stylesheet" href="../../web/css/dashboard.css" type="text/css"/>

    <script type="text/javascript" src="../../web/js/general.js?ver=012115"></script>
    <script type="text/javascript" src="../../mathjax/MathJax.js?config=AM_HTMLorMML"></script>
    <script type="text/javascript">noMathRender = false;
        var usingASCIIMath = true;
        var AMnoMathML = true;
        var MathJaxCompatible = true;
        function rendermathnode(node) {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]);
        }
    </script>
    <script src="../../web/js/ASCIIsvg_min.js?ver=012314" type="text/javascript"></script>
    <script type="text/javascript">var usingASCIISvg = true;</script>
    <script type="text/javascript" src="../../web/js/tablesorter.js"></script>
</head>
<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',0)" onmouseout="mclosetime()"></div>
        <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>


        <div class=breadcrumb><a href="/IMathAS/index.php">Home</a> &gt; Admin
        </div>
        <div id="headeradmin" class="pagetitle"><h2>IMathAS Administration</h2></div>
        <h3>Courses</h3>

        <div class=item>
            <table class=gb border=0 width="90%">
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
            <input type=button value="Add New Course" onclick="window.location='forms.php?action=addcourse'"/>
            Show courses of: <select name="seluid" id="seluid" onchange="showcourses()">
                <option value="0" selected>Select a user..</option>
                <option value="1">admin123, admin (admin)</option>
                <option value="10">dfhfng, fdbfg (pradeep)</option>
                <option value="18">hase, swati (swatimagar)</option>
                <option value="24">ravi, ravi (ravi)</option>
                <option value="8">Surve, manya (manu)</option>
            </select>
        </div>


        <h3>Administration</h3>

        <div class=cp>
            <A HREF="forms.php?action=chgpwd">Change my password</a><BR>
            <A HREF="../help.php?section=administration">Help</a><BR>
            <A HREF="actions.php?action=logout">Log Out</a><BR>
        </div>
        <div class=cp>
	<span class=column>
	<a href="../course/manageqset.php?cid=admin">Manage Question Set</a><BR>
	<a href="export.php?cid=admin">Export Question Set</a><BR>
	<a href="import.php?cid=admin">Import Question Set</a><BR>
	</span>
	<span class=column>
	<a href="../course/managelibs.php?cid=admin">Manage Libraries</a><br>
	<a href="exportlib.php?cid=admin">Export Libraries</a><BR>
	<a href="importlib.php?cid=admin">Import Libraries</a></span>

	<span class=column>
	<a href="forms.php?action=listgroups">Edit Groups</a><br/>
	<a href="forms.php?action=deloldusers">Delete Old Users</a><br/>
	<a href="importstu.php?cid=admin">Import Students from File</a>
	</span>
	<span class="column"><a href="forms.php?action=importmacros">Install Macro File</a><br/>
<a href="forms.php?action=importqimages">Install Question Images</a><br/>
<a href="forms.php?action=importcoursefiles">Install Course Files</a><br/>
</span><span class="column"><a href="forms.php?action=listltidomaincred">LTI Provider Creds</a><br/>
<a href="externaltools.php?cid=admin">External Tools</a><br/>
<a href="../util/utils.php">Admin Utilities</a><br/>
</span>

            <div class=clear></div>
        </div>

        <h4>Diagnostics</h4>

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

                    <td><a href="/IMathAS/diag/index.php?id=1">Check Here</a></td>
                    <td class=c>Yes</td>
                    <td class=c>Yes</td>
                    <td><a href="diagsetup.php?id=1">Modify</a></td>
                    <td><a href="forms.php?action=removediag&id=1">Remove</a></td>
                    <td><a href="diagonetime.php?id=1">One-time Passwords</a></td>
                </tr>
                </tbody>
            </table>
            <input type=button value="Add New Diagnostic" onclick="window.location='/IMathAS/admin/diagsetup.php'">
        </div>

        <h4>Pending Users</h4>

        <div class=item>
            <table class=gb width="90%" id="myTable">
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

            <input type=button value="Add New User" onclick="window.location='forms.php?action=newadmin'">

            <select name="selgrpid" id="selgrpid" onchange="showgroupusers()">
                <option value="-1" selected>Pending</option>
                <option value="0">Default</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
                <option value="I">I</option>
                <option value="J">J</option>
                <option value="K">K</option>
                <option value="L">L</option>
                <option value="M">M</option>
                <option value="N">N</option>
                <option value="O">O</option>
                <option value="P">P</option>
                <option value="Q">Q</option>
                <option value="R">R</option>
                <option value="S">S</option>
                <option value="T">T</option>
                <option value="U">U</option>
                <option value="V">V</option>
                <option value="W">W</option>
                <option value="X">X</option>
                <option value="Y">Y</option>
                <option value="Z">Z</option>
            </select>

            <p>Passwords reset to: password</p>
        </div>

        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
</html>