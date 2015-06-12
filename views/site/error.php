<?php

use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
$errorCode = $exception->getCode();
?>
<div class="site-error">
    <div class="col-lg-offset-3 col-lg-6 center">
        <?php if($errorCode == 2){ ?>
                <p>
                    Page not found!!!
                </p>
        <?php }else if($errorCode == 1){?>
                <p>
                    An internal server error occurred.
                </p>
        <?php }?>
        <p>
            Please contact us if you think this is a server error. Thank you.
        </p>
    </div>
</div>