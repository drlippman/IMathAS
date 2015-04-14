<!DOCTYPE html>
<html>
<head>
    <title>IMathAS</title>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome"><h2>Welcome to
                IMathAS, <?php print_r(ucfirst($user->FirstName) . ' ' . ucfirst($user->LastName)); ?><span class="red"></span>
        </div>
        <div id="homefullwidth">
            <?php echo $this->render('_adminCourseTeaching'); ?>
            <?php echo $this->render('_courseTaking'); ?>
        </div>
        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>

<!--<script type="text/javascript">var usingASCIISvg = true;</script>-->
<!--<script type="text/javascript" src="../../web/js/tablesorter.js"></script>-->

</html>