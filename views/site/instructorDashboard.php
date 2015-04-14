<!DOCTYPE html>
<html>
<head>
    <title>IMathAS</title>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../../web/css/imascore.css?ver=030415" type="text/css"/>
    <link rel="stylesheet" href="../../web/css/default.css?v=121713" type="text/css"/>
    <link rel="stylesheet" href="../../web/css/handheld.css" media="handheld,only screen and (max-device-width:480px)"/>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link rel="stylesheet" href="../../web/css/dashboard.css" type="text/css"/>

</head>
<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">

        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome"><h2>Welcome to
                IMathAS, <?php print_r(ucfirst($user->FirstName) . ' ' . ucfirst($user->LastName)); ?></h2></div>
        <div id="homefullwidth">
            <?php echo $this->render('_courseTeaching'); ?>
            <?php echo $this->render('_courseTaking'); ?>
        </div>
        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
<script src="../../web/js/jquery.min.js" type="text/javascript"></script>
<script src="../../web/js/dashboard.js" type="text/javascript"></script>
<script type="text/javascript" src="../../mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script src="../../web/js/ASCIIsvg_min.js?ver=012314" type="text/javascript"></script>
<script type="text/javascript">var usingASCIISvg = true;</script>
<script type="text/javascript" src="../../web/js/tablesorter.js"></script>
</html>
