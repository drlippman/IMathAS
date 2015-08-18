<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t(' Manage Calendar Items',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Manage Calendar Items',false)],
        'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t(' Manage Event',false);?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox">
    <div class="calendar-manage-event col-md-12">
        <?php $form = ActiveForm::begin([
            'id' => 'manage-event',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'manage-events?cid='.$course->id,
            'fieldConfig' => [
                'template' => "<div class=\"col-sm-12\">{input}</div>",
                'labelOptions' => ['class' => 'text-align-center'],
            ],
        ]); ?>
        <div>This page allows you to add events to the calendar.  Course items automatically place themselves on the calendar.</div>
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
                <tr><td class="col-sm-1"><?= $form->field($model, 'delete')->checkbox(['name'=>'delete['.$item->id.']','class'=>'delete-checkbox']); ?></td>
                    <td class="col-sm-2">     <?php
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

                    <td class="col-sm-2"><?= $form->field($model, 'tag')->textInput(['name'=>'tag['.$item->id.']','class'=>'tag-event form-control']); ?></td>
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
            <tr><td class="col-sm-2">     <?php
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
                <td class="col-sm-2"><?= $form->field($model, 'newTag')->textInput(); ?></td>
                <td ><?= $form->field($model, 'newEventDetails')->textInput(); ?></td></tr>
            </tbody>
        </table>
        <div class="col-lg-offset-1 event-submit">
            <?= Html::submitButton('Save and Add another', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit','value'=>'AddSave']) ?>
            <?= Html::submitButton('Save Changes', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit','value'=>'Save',]) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<script type="javascript">
    $('.event-data').DataTable();
</script>