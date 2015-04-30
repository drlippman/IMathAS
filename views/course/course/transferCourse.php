<?php
/* @var $this yii\web\View */
use app\components\AppUtility;

$this->title = 'Admin';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer Course Owner</title>
    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
    <!-- DataTables CSS -->
</head>
<body>
<div class=mainbody>
    <div id="headeradmin" class="pagetitle"><h2>Transfer Course Owner</h2></div>

<div>
    Transfer course ownership to <select name="seluid" class="dropdown" id="seluid" onchange="showcourses()">
        <option value="0" selected>Select a user..</option>
        <?php foreach ($users as $user) { ?>
            <option
                value="<?php echo $user['id'] ?>"><?php echo $user['FirstName'] . " " . $user['LastName']; ?></option>
        <?php } ?>
    </select>
</div>

    <div class="buttonAlignment">
        <a class="btn btn-primary" >Transfer</a>
        <a class="btn btn-primary" >Cancel</a>
    </div>

</div>


</body>
</html>
