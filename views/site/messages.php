<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Messages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div><a href="">Limit to Tagged</a> | <a href="">Sent Messages</a>
    | <?= Html::submitButton('picture', ['class' => 'class="col-lg-offset-1 col-lg-11"', 'name' => 'login-button']) ?>
</div>

<p>Filter by course: <select id="filtercid">
        <option value="0" selected=1>All courses</option>
        <option value="4"></option>
    </select> By sender:
    <select id="filteruid">

        With Selected: <input type=submit name="unread" value="Mark as Unread">
        <input type=submit name="markread" value="Mark as Read">
        <input type=submit name="remove" value="Delete">

        <table class=gb id="myTable">
            <thead>
            <tr>
                <th></th>
                <th>Message</th>
                <th>Replied</th>
                <th></th>
                <th>Flag</th>
                <th>From</th>
                <th>Course</th>
                <th>Sent</th>
            </tr>
            </thead>
        </table>
        </div>
    </select>
</p>