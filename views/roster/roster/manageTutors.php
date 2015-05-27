<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Manage Tutors';
$this->params['breadcrumbs'][] = ['label' => 'List Users', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>

<link rel="stylesheet" type="text/css"
      href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>

<h2>Manage Tutors</h2>

<div class="message-container">

<table class='message-table-show display-message-table' id='message-table-show display-message-table'>
    <thead>
    <tr>
        <th>Tutor Name</th>
        <th>Limit to Section</th>
        <th>Remove? Check<a> all </a>/<a> None</a></th>
    </tr>
    </thead>
    <tbody class='tutor-table-body'></tbody>
</table>

</div>
<br>
<br>
<p><b>Add new tutors.</b> Provide a list of usernames below, separated by commas, to add as tutors.</p>
<br>
<textarea name="newtutors" rows="3" cols="60"></textarea><br><br>
<a class="btn btn-primary" id="update-btn">Update</a>
<script type="text/javascript">
    $(document).ready(function () {

    });

    </script>