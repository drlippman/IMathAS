<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = Yii::t('yii', 'Diagnostics');

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>
</div>