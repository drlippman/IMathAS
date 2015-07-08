<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Delete Course';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_flashMessage')?>
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2'],
        ],
    ]); ?>

    <?php
        echo'Are you sure you want to delete the course:'.$course->id;
        AppUtility::dump($course->id);
    ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Delete', ['class' => 'btn btn-primary', 'name' => 'delete-button']) ?>
            <?= Html::submitButton('Nevermind', ['class' => 'btn btn-primary', 'name' => 'Nevermind-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
