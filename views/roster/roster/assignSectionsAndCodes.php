<?php

use app\components\AppUtility;
use yii\helpers\Html;

$this->title = 'Assign Section And Codes';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<h3>Assign Section/Code Numbers</h3>

<form method="post" action="assign-sections-and-codes?cid=<?php echo $cid ?>">
    <input type="hidden" id="course-id" value="<?php echo $cid ?>">

    <table class="student-data table table-bordered table-striped table-hover data-table" bPaginate="false" id="student-data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Section</th>
                <th>Code</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($studentInformation as $singleStudentInformation) { ?>
            <tr>
                <td><?php echo $singleStudentInformation['Name']?></td>
                <td><input type="text" value="<?php echo $singleStudentInformation['section']?>"
                           name='section[<?php echo $singleStudentInformation['userid']?>]'></td>
                <td><input type="text" value="<?php echo $singleStudentInformation['code']?>"
                           name='code[<?php echo $singleStudentInformation['userid']?>]'></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <input type="submit" class="btn btn-primary" id="change-button">
    <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
</form>

