<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use \app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

$this->title = 'About';
?>
<div class="col-lg-12">
    <h1><?php echo Html::encode($this->title) ?></h1>
</div>
<div class="col-lg-8 text-just"<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"ify">

    <p><?php AppUtility::t('OpenMath is a web based mathematics assessment and course management platform.') ?></p>
    <table>
        <tbody>
        <tr>
            <td>
                <img class="about-page" src="<?php echo AppUtility::getHomeURL() ?>img/screens.jpg" alt="Computer screens"/>
            </td>
            <td>

                <p><?php AppUtility::t('This system is designed for mathematics, providing delivery of homework, quizzes, tests, practice
                    tests,and diagnostics with rich mathematical content. Students can receive immediate feedback on
                    algorithmically generated questions with numerical or algebraic expression answers.') ?>
                </p>

                <p><?php AppUtility::t('If you already have an account, you can log on using the box to the right.') ?></p>

                <p><?php AppUtility::t('If you are a new student to the system,') ?> <a href="<?php echo AppUtility::getURLFromHome('site', 'student-register') ?>"><?= Yii::t('yii', 'Register as a new student') ?></a></p>
                <p><?php AppUtility::t('If you are an instructor, you can ') ?><a href="<?php echo AppUtility::getURLFromHome('site', 'registration') ?>"><?= Yii::t('yii', 'request an account') ?></a></p>

            </td>
        </tr>
        </tbody>
    </table>
    <p><?php AppUtility::t('Also available:') ?>
    <ul>
        <li><a href="#"><?php AppUtility::t('Help for student with entering answers') ?></a></li>
        <li><a href="#"><?php AppUtility::t('Instructor Documentation') ?></a></li>
    </ul>
</div>

<?php $this->title = 'Login'; ?>

<div class="site-login col-lg-4" style="border: 1px solid #000000">
    <h3><?php echo Html::encode($this->title) ?></h3>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-6\">{input}</div>\n<div class=\"col-lg-10 col-lg-offset-3\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 control-label'],
        ],
    ]); ?>

    <?php echo $form->field($model, 'username') ?>
    <?php echo $form->field($model, 'password')->passwordInput() ?>

    <input type="hidden" id="tzoffset" name="tzoffset" value="">
    <input type="hidden" id="tzname" name="tzname" value="">
    <input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>"/>

    <div id="settings"></div>

    <div class="form-group select-text-margin">
        <div class="col-lg-offset-3 col-lg-3 select-text-margin">
            <?php echo Html::submitButton('Login', ['class' => 'btn btn-primary btn-min-width', 'id' => 'enroll-btn', 'name' => 'login-button']) ?>
        </div>
    </div>
    <div class="select-text-margin"></div>
    <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'student-register'); ?>">Register as a new student</a></p>
    <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-password'); ?>">Forgot Password</a></p>
    <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-username'); ?>">Forgot Username</a></p>
    <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'check-browser'); ?>">Browser check</a></p>


    <?php ActiveForm::end(); ?>

</div>
