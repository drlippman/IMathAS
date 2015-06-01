<?php

use app\components\AppUtility;
use yii\helpers\Html;

$this->title = 'Assign Section And Codes';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid=' . $_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid=' . $_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" type="text/css"
      href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>

<h3>Assign Section/Code Numbers</h3>

<form method="post" action="assign-sections-and-codes?cid=<?php echo $cid ?>">
    <input type="hidden" id="course-id" value="<?php echo $cid ?>">

    <table class="student-data" id="student-data-table">
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
    <input type="submit" class="btn btn-primary">
</form>

<script type="text/javascript">
    $(document).ready(function () {
        $('.student-data').DataTable();
    });


</script>