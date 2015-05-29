<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Thread';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/forums.css"/>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css"
      href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>



Search: <input type=text name="search" />  <input type=checkbox name="allforums" /> All forums in course? <input type="submit" value="Search"/>

<p><button type="button" onclick="#">Add New Thread</button>
<input type="hidden" id="forumid" value="<?php echo $forumid ?>">
<table id="forum-table displayforum" class="forum-table">
    <thead>
    <tr>
        <th>Topic</th>
        <th>Replies</th>
        <th>Views</th>
        <th>Last Post Date</th>

    </tr>
    </thead>
    <tbody class="forum-table-body">
    </tbody>
</table>

<script>

    $(document).ready(function ()
    {
        var forumid= $('#forumid').val();
        jQuerySubmit('get-thread-ajax',{forumid: forumid },'threadSuccess');

    });

    function threadSuccess(response)
    {
        var result = JSON.parse(response);
        var fid= $('#forumid').val();

        if (result.status == 0)
        {
             var threads = result.threadData;
            var html = "";
            $.each(threads, function(index, thread){

                    html += "<tr> <td><a href='#'>" +(thread.subject) + "</a></td>+ <a href='Modify'> ";
                    html += "<td>" + thread.replyby + "</td>";
                    html += "<td>" + thread.views + "</td>";
                    html += "<td>" + thread.postdate + "</td>";

            });
            $(".forum-table-body").append(html);
            $('.forum-table').DataTable();



        }

    }


</script>



