<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = 'Help for student entering answers';
//$this->params['breadcrumbs'][] = ['label' => 'About Us', 'url' => ['/site/about']];
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent",['link_title'=>['Home','About Us'], 'link_url' => [AppUtility::getHomeURL(),AppUtility::getHomeURL().'site/login'], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
<div class="site-about padding-right-left-top-thirty padding-bottom-thirty">
    <div style="background-color: #fafafa;" class="padding-bottom-twenty padding-right-left-top-thirty">
    <img class="floatleft student-help-image" src="<?php echo AppUtility::getHomeURL() ?>img/typing.jpg" alt="Computer screens"/>
    <div class="content">
        <h4 class="margin-top-minus-two"><?php AppUtility::t('Answer Types');?></h4>
        <p class="ind">
            <?php AppUtility::t('Each question requests a specific type of answer.  Usually a question will display a hint
            at the end of the question as to what type of answer is expected.  In addition to
            multiple choice questions and other standard types, this system also features several mathematical
            answer types.  Read on for suggestions on entering answers for these types.')?></p>

        <h4><?php AppUtility::t('Numerical Answers');?></h4>
        <p class="ind">
            <?php AppUtility::t('Some question ask for numerical answers.  Acceptable answers include whole numbers, integers (negative numbers), and decimal values.')?>
        </p>
        <p class="ind">
            <?php AppUtility::t('In special cases, you may need to enter DNE for "Does not exist", oo for infinity, or -oo for negative infinity.')?>
        </p>
        <p class="ind">
            <?php AppUtility::t('If your answer is not an exact value, you will want to enter at least 3 decimal places unless the problem specifies otherwise.')?>
        </p>

        <h4><?php AppUtility::t('Fractions and Mixed Numbers')?></h4>
        <p class="ind">
            <?php AppUtility::t('Some questions ask for fractions or mixed number answers.')?>
        </p>
        <p class="ind"><?php AppUtility::t('Enter fractions like 2/3 for')?><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/23.gif">.<?php AppUtility::t(' If not specified, the fraction
            does not need to be reduced, so ')?><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/24.gif"><?php AppUtility::t(' is considered the same as ')?><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/12.gif">.</p>
        <p class="ind">
            <?php AppUtility::t('For a reduced fraction answer, be sure to reduce your fraction to lowest terms.')?>
            <?php AppUtility::t('If')?> <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/12.gif"> <?php AppUtility::t('is the correct answer')?>, <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/24.gif"> <?php AppUtility::t('is not reduced, and would be marked wrong.')?></p>

        <p class="ind"><?php AppUtility::t('For both fraction and reduced fraction type problems, improper
            fractions are OK, like')?> <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/103.gif">, <?php AppUtility::t('but mixed numbers will not be accepted.')?></p>

        <p class="ind">
            <?php AppUtility::t('For a mixed number problem, enter your answer like 5_1/3 for')?> <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/513.gif">. <?php AppUtility::t('Improper
            fractions will not be accepted. Also, be sure to reduce the fractional portion of the mixed number to lowest terms.')?></p>

        <h4><?php AppUtility::t('Calculations')?></h4>
        <p class="ind"><?php AppUtility::t('Some questions allow calculations be entered as answers.  You can also enter whole numbers,
            negative numbers, or decimal numbers.  If you enter a decimal value, be sure to give')?> <i><?php AppUtility::t('at least')?></i> <?php AppUtility::t('3 decimal places.')?></p>

        <p class="ind">
            <?php AppUtility::t('Alternatively, you can enter mathematical expressions.  Some examples:')?>
        <table class="bordered ind"><tr><th><?php AppUtility::t('Enter')?></th><th><?php AppUtility::t('To get')?></th></tr>
            <tr><td>sqrt(4)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/s4.gif"> = 2</td></tr>
            <tr><td>2/(5-3)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/2o5m3.gif"> = 1</td></tr>
            <tr><td>3^2</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/3s.gif"> = 9</td></tr>
            <tr><td>sin(pi)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/sinpi.gif"> = 0</td></tr>
            <tr><td>arctan(1)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/arctan1.gif"> (note: tan^-1(1) will not work)</td></tr>
            <tr><td>log(100)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/log100.gif"> = 2</td></tr>
        </table>
        <p class="ind"><?php AppUtility::t('Note that when entering functions like sqrt and sin, use parentheses around the input.  sin(3) is ok, but sin3 is not.')?>
        </p>
        <p class="ind"><?php AppUtility::t('You can use the Preview button to see how the computer is interpreting what you have entered')?></p>

        <h4><?php AppUtility::t('Algebraic Expressions')?></h4>
        <p class="ind"><?php AppUtility::t('Some questions ask for algebraic expression answers.  With these types of questions,
            be sure to use the variables indicated.  In your answer, you can also use mathematical expressions for numerical calculations.')?></p>

        <p class="ind">
            <?php AppUtility::t('Examples:')?>
        <table class="bordered margin-left-twentyone">
            <tr><th><?php AppUtility::t('Type')?></th><th><?php AppUtility::t('To get')?></th></tr>
            <tr><td>-3x^2+5</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/m3xs.gif"></td></tr>
            <tr><td>(2+x)/(3-x)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/2pxo3mx.gif"></td></tr>
            <tr><td>sqrt(x-5)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/sxm5.gif"></td></tr>
            <tr><td>3^(x+7)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/3txp7.gif"></td></tr>
            <tr><td>1/(x(x+1))</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/1oxtxp1.gif"></td></tr>
            <tr><td>5/3x+2/3</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/53x.gif"></td></tr>
            <tr><td>sin(pi/3x)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/sinpio3x.gif"></td></tr>
            <tr><td>ln(x)/ln(7)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/lnoln.gif"></td></tr>
            <tr><td>arcsin(x)</td><td><img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/arcsinx.gif"></td></tr>
        </table>
        </p>
        <p class="ind">
            <?php AppUtility::t('With any function like sqrt, log, or sin, be sure to put parentheses around the input:  sqrt(x+3) is OK, but sqrtx+3 or sqrt x+3 is not.')?></p>

        <p class="ind"><?php AppUtility::t('Note that the shorthand notation sin^2(x) will display correctly but not evaluate correctly; use (sin(x))^2 instead.')?></p>

        <p class="ind">
            <?php AppUtility::t('Unless the problem gives specific directions, any algebraically equivalent expression is acceptable.
            For example, if the answer was')?> <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/f1.gif">, then<img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/f2.gif"> and <img src="<?php echo AppUtility::getHomeURL() ?>img/answerimgs/f3.gif"> would also be acceptable.</p>

        <p class="ind">
            <?php AppUtility::t('You can use the Preview button to see how the computer is interpreting your answer.  If you see "syntax ok", it means the computer
            can understand what you typed (though it may not be the correct answer).  If you see "syntax error", you may be missing a
            parenthese or have used the wrong variables in your answer.')?></p>
    </div>
</div>
</div>
</div>