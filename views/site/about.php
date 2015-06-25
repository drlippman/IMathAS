<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = Yii::t('yii', 'About Us');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

        <p><?= Yii::t('yii', 'IMathAS is a web based mathematics assessment and course management platform.') ?></p>
    <table>
        <tbody>
        <tr>
            <td>
                <img class="about-page" src="<?php echo AppUtility::getHomeURL() ?>img/screens.jpg" alt="Computer screens"/>
            </td>
            <td>
                <p><?= Yii::t('yii', 'This system is designed for mathematics, providing delivery of homework, quizzes, tests, practice
                    tests,and diagnostics with rich mathematical content. Students can receive immediate feedback on
                    algorithmically generated questions with numerical or algebraic expression answers.') ?>
                </p>

                <p><?= Yii::t('yii', 'If you already have an account, you can log on using the box to the right.') ?></p>

                <p><?= Yii::t('yii', 'If you are a new student to the system,') ?> <a href="<?php echo AppUtility::getURLFromHome('site', 'student-register') ?>"><?= Yii::t('yii', 'Register as a new student') ?></a></p>
                <p><?= Yii::t('yii', 'If you are an instructor, you can') ?><a href="<?php echo AppUtility::getURLFromHome('site', 'registration') ?>"><?= Yii::t('yii', 'request an account') ?></a></p>

            </td>
        </tr>
        </tbody>
    </table>
    <p><?= Yii::t('yii', 'Also available:') ?>
    <ul>
        <li><a href="#"><?= Yii::t('yii', 'Help for student with entering answers') ?></a></li>
        <li><a href="#"><?= Yii::t('yii','Instructor Documentation') ?></a></li>
    </ul>
</div>