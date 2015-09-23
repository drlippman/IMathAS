<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'Diagnostic Setup';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php AppUtility::includeCSS('adminDiagnostic.css'); ?>

<div class="site-login">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 control-label text-align-left'],
        ],
    ]); ?>

    <div class="form-label-alignment">
        <?= $form->field($model, 'DiagnosticName') ?>

        <?= $form->field($model, 'TermDesignator')->inline()->radioList([AppConstant::NUMERIC_ONE => 'Use Month', AppConstant::NUMERIC_TWO => 'Use Day', AppConstant::NUMERIC_THREE => 'use'], ['class' => 'radio-div']) ?>

        <?= $form->field($model, 'LinkedWithCourse')->dropDownList(array(''), ['prompt' => 'Default']) ?>

        <?= $form->field($model, 'Available')->inline()->radioList([AppConstant::NUMERIC_ONE => 'Yes', AppConstant::NUMERIC_ONE => 'No'], ['class' => 'radio-div']) ?>

        <?= $form->field($model, 'IncludeInPublicListing')->inline()->radioList([AppConstant::NUMERIC_ONE => 'Yes', AppConstant::NUMERIC_TWO => 'No'], ['class' => 'radio-div']) ?>

        <?= $form->field($model, 'AllowReentry')->inline()->radioList([AppConstant::NUMERIC_ONE => 'No', AppConstant::NUMERIC_TWO => 'Yes'], ['class' => 'radio-div']) ?>

        <?= $form->field($model, 'UniqueIDPrompt') ?>

        <div
            class="form-checkbox-description"><?= $form->field($model, 'AttachFirstLevelSelectorToID')->checkbox() ?>
        </div>

        <?= $form->field($model, 'IDEntryFormat')->dropDownList(array(''), ['prompt' => 'Letters or Number']) ?>

        <?= $form->field($model, 'IDEntryNumberOfCharacters')->dropDownList(array(''), ['prompt' => 'Any Number']) ?>

        <?php echo '<span class="form-lable-discription">Allow access without password from computer with these IP addresses. Use * for wildcard, e.g. 134.39.*</span>' ?>
        <?= $form->field($model, 'EnterIPAddress') ?>

        <?php echo '<span class="form-lable-discription">From other computers, a password will be required to access the diagnostic.</span>' ?>
        <?= $form->field($model, 'EnterPassword') ?>

        <?php echo '<span class="form-lable-discription">Super passwords will override testing window limits.</span>' ?>

        <?= $form->field($model, 'SuperPasswords') ?>

        <?php echo '<span class="form-lable-discription">First-level selector - selects assessment to be delivered</span> ' ?>

        <?= $form->field($model, 'SelectorName') ?>

        <div
            class="form-checkbox-description"><?= $form->field($model, 'AlphabetizeSelectorsOnSubmit')->checkbox() ?>
        </div>

        <?= $form->field($model, 'EnterNewSelectorOption') ?>

    </div>

    <div class="form-group">
        <div class="col-lg-offset-3 col-lg-9">
            <?= Html::submitButton('Continue Setup', ['class' => 'btn btn-primary btn-continue', 'name' => 'continue-    setup-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
