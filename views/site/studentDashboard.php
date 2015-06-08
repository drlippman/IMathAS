<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

?>
<title>IMathAS</title>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">

        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome">
            <h2>Welcome to IMathAS, <?php echo AppUtility::getFullName($user->FirstName, $user->LastName) ?></h2>
        </div>
        <?php echo $this->render('_courseTaking', ['students' => $students]); ?>

        <div class="clear"></div>
    </div>
</div>

