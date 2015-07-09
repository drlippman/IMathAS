
<?php
use yii\helpers\Html;
use app\assets\AppAsset;
use app\components\AppUtility;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <link href='<?php echo AppUtility::getHomeURL(); ?>css/master.css?<?php echo time(); ?>' rel='stylesheet' type='text/css'>
</head>

<body>
<?php $this->beginBody() ?>
<div class="header-content">
    <?php echo $this->render( '_header'); ?>
</div>
<div class="clear-both"></div>
<div class="master-wrap">

    <div class="master-container">
        <div class="container-upper-blue">
            <div class="graphic-math-img">
            </div>
            <div class="clear-both"></div>
        </div>
        <div class="container-lower-white">
            <div class="master-items-container">
                <div class="page-content">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
<div class="clear-both"></div>
</body>
<?php echo $this->render( '_footer'); ?>
</html>
<?php $this->endPage() ?>