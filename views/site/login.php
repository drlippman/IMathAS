<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to login:</p>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?php echo Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'password')->passwordInput() ?>

    <input type="hidden" id="tzoffset" name="tzoffset" value="">
    <input type="hidden" id="tzname" name="tzname" value="">
    <input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
    <div id="settings"></div>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Login', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <p><a href="work-in-progress">Register as a new student</a></p>
    <p><a href="work-in-progress">Forgot Password</a></p>
    <p><a href="work-in-progress">Forgot Username</a></p>
    <p><a href="work-in-progress">Browser check</a></p>

</div>

<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;</script>
<script type="text/javascript" src="../../mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script type="text/javascript">
    var thedate = new Date();
    document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
//    var tz = jstz.determine();
    console.log('hi');
//    console.log(tz.name());
//    document.getElementById("tzname").value = tz.name();

    function updateloginarea() {
        setnode = document.getElementById("settings");
        var html = "";
        html += '<div style="margin-top: 0px; margin-bottom: 10px; margin-right:0px;text-align:left;padding:0px">Accessibility: <select name="access"><option value="0">Use defaults</option>';
        html += '<option value="3">Force image-based display</option>';
        html += '<option value="1">Use text-based display</option></select> ';
        html += "<a href='#' onClick=\"window.open('/open-math/web/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help</a> </div>";

  //      if (!MathJaxCompatible) {
     //       html += '<input type=hidden name="mathdisp" value="0">';
   //     } else {
            html += '<input type=hidden name="mathdisp" value="1">';
//        }
        if (ASnoSVG) {
            html += '<input type=hidden name="graphdisp" value="2">';
        } else {
            html += '<input type=hidden name="graphdisp" value="1">';
        }
 //       if (MathJaxCompatible && !ASnoSVG) {
            html += '<input type=hidden name="isok" value=1>';
 //       }
   //     html += '<div class=textright><input type="submit" value="Login"></div>';
        setnode.innerHTML = html;
    //    document.getElementById("username").focus();
    }
    var existingonload = window.onload;
    if (existingonload) {
        window.onload = function() {existingonload(); updateloginarea();}
    } else {
        window.onload = updateloginarea;
    }
</script>
