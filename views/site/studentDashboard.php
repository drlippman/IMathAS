<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<title>IMathAS</title>
<body>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">

        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome">
            <h2>Welcome to IMathAS, <?php print_r(ucfirst($user->FirstName) . ' ' . ucfirst($user->LastName)); ?></h2>
        </div>
        <?php echo $this->render('_courseTaking', ['students' => $students]); ?>

        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>
</body>
