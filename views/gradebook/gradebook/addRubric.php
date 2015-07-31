<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Add Rubric';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid=' . $course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Add Grade', 'url' => ['/gradebook/gradebook/add-grades?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<fieldset xmlns="http://www.w3.org/1999/html">
    <legend>Add Rubric</legend>
    <?php
    if (!$rubricId) {
        $form = ActiveForm::begin([
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'add-rubric?cid=' . $course->id,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
            ],
        ]);
        $model->Name = AppUtility::getStringVal('New Rubrics');
        $rubicData['groupid'] = -1;
    } else {
        $form = ActiveForm::begin([
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'add-rubric?cid=' . $course->id . '&rubricId=' . $rubricId,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
            ],
        ]);

        $model->Name = AppUtility::getStringVal($rubicData['name']);
    }

    ?>
    <?php
    $model->RubricType = AppUtility::getStringVal($rubicData['rubrictype']);
    ?>
    <?= $form->field($model, 'Name')->textInput(); ?>
    <?php
    $rubtypeval = array(1, 0, 3, 4, 2);
    $rubtypelabel = array('Score breakdown, record score and feedback', 'Score breakdown, record score only', 'Score total, record score and feedback', 'Score total, record score only', 'Feedback only');

     ?>
    <span class=form>Rubric Type</span>
    <div class="col-lg-offset-3"> <?php
    AssessmentUtility::writeHtmlSelect('rubtype', $rubtypeval, $rubtypelabel, $rubicData['rubrictype'], null, null, 'onchange="imasrubric_chgtype()"');

    ?>
        <br class=form>


        <input type="checkbox" value="1"<?php if ($rubicData['groupid'] == 0) {
            echo "checked=1";
        } ?> name="ShareWithGroup"> ShareWithGroup
        <br class=form>
        <br class=form>

       <?php

        echo '<table><thead>
        <tr>
        <th>Rubric Item<br/>Shows in feedback</th>
        <th>Instructor Note<br/>Not in feedback</th>
        <th><span id="pointsheader" ';
        if ($rubicData['rubrictype'] == 2) {
            echo 'style="display:none;" ';
        }
        if ($rubicData['rubrictype'] == 3 || $rubicData['rubrictype'] == 4) {
            echo '>Percentage of score</span>';
        } else {
            echo '>Percentage of score<br/>Should add to 100</span>';
        }
        echo '</th>
        </tr>
        </thead><tbody>';
         for ($i = 0; $i < 15; $i++) {
            echo '<tr><td><input type="text" size="40" name="rubitem[' . $i . ']" value="';
            if (isset($rubricItems[$i]) && isset($rubricItems[$i][0])) {
                echo str_replace('"', '&quot;', $rubricItems[$i][0]);
            }
            echo '"/></td>';
            echo '<td><input type="text" size="40" name="rubnote[' . $i . ']" value="';
            if (isset($rubricItems[$i]) && isset($rubricItems[$i][1])) {
                echo str_replace('"', '&quot;', $rubricItems[$i][1]);
            }
            echo '"/></td>';
            echo '<td><input type="text" size="4" class="rubricpoints" ';
            if ($rubicData['rubrictype'] == 2) {
                echo 'style="display:none;" ';
            }
            echo 'name="feedback[' . $i . ']" value="';
            if (isset($rubricItems[$i]) && isset($rubricItems[$i][2])) {
                echo str_replace('"', '&quot;', $rubricItems[$i][2]);
            } else {
                echo 0;
            }
            echo '"/></td></tr>';
        }
        echo '</table>'; ?>

        <div class="form-group">
            <div class=" col-lg-8 display_field">
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3']) ?>
                <a class="btn btn-primary back-button-change-student-info"
                   href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course->id) ?>">Back</a>
            </div>
        </div>
    </div>
</fieldset>

<?php ActiveForm::end(); ?>
<script>
    function imasrubric_chgtype() {
        var val = document.getElementById("rubtype").value;
        els = document.getElementsByTagName("input");
        for (i in els) {
            if (els[i].className=='rubricpoints') {
                if (val==2) {
                    els[i].style.display = 'none';
                    document.getElementById("pointsheader").style.display = 'none';
                } else {
                    els[i].style.display = '';
                    document.getElementById("pointsheader").style.display = '';
                    if (val==0 || val==1) {
                        document.getElementById("pointsheader").innerHTML='Percentage of score<br/>Should add to 100';
                    } else if (val==3 || val==4) {
                        document.getElementById("pointsheader").innerHTML='Percentage of score';
                    }
                }
            }
        }
    }
    </script>