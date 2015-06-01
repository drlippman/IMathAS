<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
//use app\widgets\FileInput;


$this->title = 'Import Students';
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
//$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>
    <link rel="stylesheet" type="text/css"
          href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>

    <div class="import-student">
        <fieldset>
            <legend>Import Students from File</legend>
        <?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
    'action' => '',
    'fieldConfig' => [
        'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
        'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
    ],
]);
        ?>
            <table id="user-table displayCourse" class="display user-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Rights</th>
                </tr>
                </thead>
                <tbody class="user-table-body">
                <?php
                $studentData = $studentData['studentData'];
                foreach ($studentData as $singleRecord) {?>
                    <tr>
                        <td><?php echo $singleRecord[0]?></td>
                        <td><?php echo $singleRecord[1]?></td>
                        <td><?php echo $singleRecord[2]?></td>
                        <td><?php echo $singleRecord[3]?></td>

                    </tr>
                <?php } ?>
                </tbody>
            </table>

        </fieldset>
        <div class="form-group">
            <div class="roster-submit">
                <?= Html::submitButton('Submit and Review', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>