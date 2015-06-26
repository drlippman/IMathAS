<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Manage Calendar Items';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
foreach ($eventItems as $item){

}

?>
<div class="calendar-manage-event">
    <fieldset>
        <legend>Manage Calendar Items</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'manage-event',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'manage-events?cid='.$course->id,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-12\">{input}</div>\n<div class=\"col-lg-4 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'text-align-center'],
            ],
        ]); ?>
        <p>This page allows you to add events to the calendar.  Course items automatically place themselves on the calendar.</p>
        <h4>Manage Events</h4>
        <table class="event-data table table-bordered table-striped table-hover data-table">
            <thead>
            <tr><th>Delete?</th><th>Date</th><th>Tag</th><th>Event Details</th></tr>
            </thead>
            <tbody>
            <?php foreach ($eventItems as $item){
                $tag = $item['tag'];
                $title = $item['title'];
                $model->tag = AppUtility::getStringVal($tag);
                $model->eventDetails = AppUtility::getStringVal($title);?>
            <tr><td class="col-lg-1"><?= $form->field($model, 'delete')->checkbox(['name'=>'delete['.$item->id.']']); ?></td>
                <td class="col-lg-2">     <?php
                    echo DatePicker::widget([
                        'name' => 'EventDate'.$item->id,
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y",$item['date']),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    ?></td>

                <td class="col-lg-2"><?= $form->field($model, 'tag')->textInput(['name'=>'tag['.$item->id.']']); ?></td>
                <td ><?= $form->field($model, 'eventDetails')->textInput(['name'=>'eventDetails['.$item->id.']']); ?></td></tr>
            <?php }?>
            </tbody>
        </table>
        <div class="col-lg-offset-1 event-submit">
            <?= Html::submitButton('Save Changes', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit','value'=>'Save']) ?>
        </div>
        <h4>Add New Events</h4>
        <table class="event-data table table-bordered table-striped table-hover data-table">
            <thead>
            <tr><th>Date</th><th>Tag</th><th>Event Details</th></tr>
            </thead>
            <tbody>
            <tr><td class="col-lg-2">     <?php
                    echo DatePicker::widget([
                        'name' => 'startDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    ?></td>
                <td class="col-lg-2"><?= $form->field($model, 'newTag')->textInput(); ?></td>
                <td ><?= $form->field($model, 'newEventDetails')->textInput(); ?></td></tr>
            </tbody>
        </table>
        <div class="col-lg-offset-1 event-submit">
            <?= Html::submitButton('Save and Add another', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit','value'=>'AddSave']) ?>
            <?= Html::submitButton('Save Changes', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit','value'=>'Save',]) ?>
        </div>
</div>

<?php ActiveForm::end(); ?>
<script type="javascript">
    $('.event-data').DataTable();
</script>
