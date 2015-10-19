<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use \app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

$this->title = 'About Us';
?>
<div class="item-detail-header">
    <h1 style="color: #ffffff"><?php echo Html::encode($this->title) ?></h1>
</div>

<div class="tab-content shadowBox margin-top-fourty login-text-font" style="padding-top: 30px">
    <div style="margin: 20px">
    <div class="col-md-8 col-sm-12 text-just mobile-hide"<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"ify">

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
            <li><a href="<?php echo AppUtility::getURLFromHome('site','helper-guide')?>"><?php AppUtility::t('Help for student with entering answers') ?></a></li>
            <li><a href="<?php echo AppUtility::getURLFromHome('site','instructor-document'); ?>"><?php AppUtility::t('Instructor Documentation') ?></a></li>
<!--            <li><a href="--><?php //echo AppUtility::getHomeURL()?><!--docs/docs.php">--><?php //AppUtility::t('Instructor Documentation') ?><!--</a></li>-->
        </ul>
    </div>

<?php $this->title = 'Login'; ?>

    <div class="site-login col-md-4 col-sm-12 height" style="border: 1px solid #a9a9a9; margin-bottom: 40px;padding-bottom: 10px">
        <h3 style="margin-top: 10px"><?php echo Html::encode($this->title) ?><a href='#' onClick="window.open('<?php echo AppUtility::getAssetURL() ?>docs/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a></h3>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-8 col-sm-6 \">{input}</div>\n<div class=\"col-md-10 col-sm-6 clear-both col-md-offset-4 col-sm-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-4 col-sm-2  control-label'],
            ],
        ]); ?>

        <?php echo $form->field($model, 'username') ?>
        <?php echo $form->field($model, 'password')->passwordInput() ?>

        <input type="hidden" id="tzoffset" name="tzoffset" value="">
        <input type="hidden" id="tzname" name="tzname" value="">
        <input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>"/>

        <div id="settings"></div>

        <div class="form-group select-text-margin">
            <div class="col-md-offset-4 col-sm-offset-2 col-md-4 col-sm-3 select-text-margin">
                <?php echo Html::submitButton('Login', ['class' => 'btn btn-primary btn-min-width', 'id' => 'enroll-btn', 'name' => 'login-button']) ?>
            </div>
        </div>
        <div class="select-text-margin" style="width: 100%"></div>
            <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'student-register'); ?>">Register as a new student</a></p>
            <p class="login-register-link mobile-show"><a href="<?php echo AppUtility::getURLFromHome('site', 'registration') ?>"><?= Yii::t('yii', 'request an account') ?></a></p>
            <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-password'); ?>">Forgot Password</a></p>
            <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-username'); ?>">Forgot Username</a></p>
            <p class="login-register-link mobile-show"><a href="<?php echo AppUtility::getURLFromHome('site','helper-guide')?>"><?php AppUtility::t('Help for student with entering answers') ?></a></p>
            <p class="login-register-link mobile-show"><a href="<?php echo AppUtility::getURLFromHome('site','instructor-document'); ?>"><?php AppUtility::t('Instructor Documentation') ?></a></p>
            <p class="login-register-link"><a href="<?php echo AppUtility::getURLFromHome('site', 'check-browser'); ?>">Browser check</a></p>

        <?php ActiveForm::end(); ?>

    </div>
  </div>
</div>
