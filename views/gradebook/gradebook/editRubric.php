<?php
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>
<fieldset xmlns="http://www.w3.org/1999/html">
    <legend>Edit Rubrics</legend>
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
        'action' => 'edit-rubric',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
        ],
    ]); ?>
    Select a rubric to edit or <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid='.$course->id) ?>" >Add new rubric</a>
<table >
    <thead>
    <tr>
        <th>Name</th>
        <th>Edit</th>

    </tr>
    </thead>
    <tbody>

    <?php
    foreach($rubicData as $singleRubricInformation){ ?>
        <tr>
            <td><pre><?php echo $singleRubricInformation['name']?></pre></td>
            <td><pre><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid='.$course->id.'&rubricId='.$singleRubricInformation['id']) ?>">Edit</a></pre></td>
        </tr>
    <?php }?>
    <tbody>
</table>
<?php ActiveForm::end(); ?>