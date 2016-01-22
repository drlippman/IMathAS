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
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name],
        'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Manage Calendar Items',false);?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);?>
</div>
<div class="tab-content shadowBox">
    <div class="calendar-manage-event col-md-12 col-sm-12">
        <?php $form = ActiveForm::begin([
            'id' => 'manage-event',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'manage-events?cid='.$course->id,
            'fieldConfig' => [
                'template' => "<div class=\"col-md-12 col-sm-12\">{input}</div>",
                'labelOptions' => ['class' => 'text-align-center'],
            ],
        ]); ?>
        <div class="col-md-12 col-sm-12 padding-left-zero padding-bottom-one-em"><?php AppUtility::t('This page allows you to add events to the calendar.  Course items automatically place themselves on the calendar.')?></div>
        <h4><?php AppUtility::t('Manage Events')?></h4>
        <table class="event-data table table-bordered table-striped table-hover data-table">
            <thead>
            <tr><th><?php AppUtility::t('Delete?')?></th><th><?php AppUtility::t('Date')?></th><th><?php AppUtility::t('Tag')?></th><th><?php AppUtility::t('Event Details')?></th></tr>
            </thead>
            <tbody>
            <?php foreach ($eventItems as $item){
                $tag = $item['tag'];
                $title = $item['title'];
                $model->tag = AppUtility::getStringVal($tag);
                $model->eventDetails = AppUtility::getStringVal($title);?>
                <tr><td class="col-md-1 col-sm-1"><?php echo $form->field($model, 'delete')->checkbox(['name'=>'delete['.$item->id.']','class'=>'delete-checkbox']); ?></td>
                    <td class="col-md-2 col-sm-4">     <?php
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

                    <td class="col-sm-2 col-md-2"><?php echo $form->field($model, 'tag')->textInput(['name'=>'tag['.$item->id.']','class'=>'tag-event form-control']); ?></td>
                    <td ><?php echo $form->field($model, 'eventDetails')->textInput(['name'=>'eventDetails['.$item->id.']']); ?></td></tr>
            <?php }?>
            </tbody>
        </table>

        <div class="floatleft padding-bottom-one-em">
            <button class="btn btn-primary page-settings" type="submit" name="Submit" value="Save"><i class="fa fa-share header-right-btn"></i><?php echo 'Save Changes' ?></button>
        </div><br/>
        <h4 class="text-align-center " style="padding-right: 111px"><?php AppUtility::t('Add New Events')?></h4>
        <table class="event-data table table-bordered table-striped table-hover data-table">
            <thead>
            <tr><th><?php AppUtility::t('Date')?></th><th><?php AppUtility::t('Tag')?></th><th><?php AppUtility::t('Event Details')?></th></tr>
            </thead>
            <tbody>
            <tr><td class="col-sm-4 col-md-2">     <?php
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
                <td class="col-sm-2 col-md-2">
<!--                    --><?php //echo $form->field($model, 'newTag')->textInput(['readonly' => !$model->isNewRecord]); ?><!--</td>-->
                <input type="text" name="newTag" value="!">
                <td ><?php echo $form->field($model, 'newEventDetails')->textInput(); ?></td></tr>
            </tbody>
        </table>

        <div class="floatleft padding-bottom-one-em">
            <button class="btn btn-primary page-settings" type="submit" name="Submit" value="AddSave"><i class="fa fa-share header-right-btn"></i><?php echo 'Save and Add another' ?></button>
            <button class="btn btn-primary page-settings margin-left-one-em" type="submit" name="Submit" value="Save"><i class="fa fa-share header-right-btn"></i><?php echo 'Save Changes' ?></button>
        </div><br/>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<script type="javascript">
    $('.event-data').DataTable();
</script>