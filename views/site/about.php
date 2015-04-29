<?php
use yii\helpers\Html;
use app\components\AppUtility;

/* @var $this yii\web\View */
$this->title = 'About';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>IMathAS is a web based mathematics assessment and course management platform. </p>
    <img style="float: left; margin-right: 20px;" src="<?php echo AppUtility::getHomeURL() ?>img/screens.jpg"
         alt="Computer screens"/>

    <p>This system is designed for mathematics, providing delivery of homework, quizzes, tests, practice tests,
        and diagnostics with rich mathematical content. Students can receive immediate feedback on algorithmically
        generated questions with
        numerical or algebraic expression answers.
    </p>
    <p>If you already have an account, you can log on using the box to the right.</p>
    <p>If you are a new student to the system, <a href="<?php echo AppUtility::getURLFromHome('site', 'student-register') ?>">Register as a new student</a></p>
    <p>If you are an instructor, you can <a href="<?php echo AppUtility::getURLFromHome('site', 'registration') ?>">request an account</a></p>
    <p>Also available:
    <ul>
        <li><a href="#">Help for student with entering answers</a></li>
        <li><a href="#">Instructor Documentation</a></li>
    </ul>

</div>