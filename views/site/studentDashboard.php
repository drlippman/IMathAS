<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<title>IMathAS</title>

<link rel="stylesheet" type="text/css" href="/open-math/web/css/dashboard.css"/>
<link rel="stylesheet" href="/open-math/web/css/imascore.css?ver=030415" type="text/css"/>
<link rel="stylesheet" href="/open-math/web/css/default.css?v=121713" type="text/css"/>

<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">

        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome">
            <h2>Welcome to IMathAS, <?php print_r(ucfirst($user->FirstName) . ' ' . ucfirst($user->LastName)); ?></h2>
        </div>
        <?php echo $this->render('_courseTaking'); ?>

        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
<script src="/open-math/web/js/jquery.min.js" type="text/javascript"></script>
<script src="/open-math/web/js/dashboard.js" type="text/javascript"></script>

