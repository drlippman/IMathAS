<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Password';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-login">
  <h3><b><?php echo $assessments->name;?></b></h3>
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2'],
        ],
    ]); ?>
    <p> Password required for access.</p>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'changepassword-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
