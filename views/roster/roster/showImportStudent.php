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
<form method="post" action="show-import-student">
<div class="import-student">
    <fieldset>
        <table id="user-table displayUser" class="display-user-table">
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
            if (isset($studentData['newUsers'])) {
                $newStudentData = $studentData['newUsers'];
                foreach ($newStudentData as $singleRecord) {
                    ?>
                    <tr>
                        <td><?php echo $singleRecord[0] ?></td>
                        <td><?php echo $singleRecord[1] ?></td>
                        <td><?php echo $singleRecord[2] ?></td>
                        <td><?php echo $singleRecord[3] ?></td>
                    </tr>
                <?php } ?>
            <?php } ?>
            <?php
            if (isset($studentData['existingUsers'])) {
                $existStudentData = $studentData['existingUsers'];
                foreach ($existStudentData as $singleRecord) {
                    ?>
                    <tr>
                        <?php
                        foreach ($singleRecord as $key => $data) {
                            ?>
                            <td><?php echo $singleRecord[$key] ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>

    </fieldset>
    <div class="form-group">
        <div class="roster-submit">

            <input type="submit" value="Submit" class = "btn btn-primary">
        </div>
    </div>
</div>
</form>
