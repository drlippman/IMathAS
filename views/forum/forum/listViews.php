<?php
use app\components\AppUtility;

$flexwidth = true;
$nologo = true;
echo '<h4>'._('Thread Views').'</h4>';


if (count($users)==0) {
    echo '<p>'._('No thread views').'</p>';
} else {
    echo '<table><thead><tr><th>'._('Name').'</th><th>'._('Last Viewed').'</th></tr></thead>';
    echo '<tbody>';
    foreach ($users as $row ) {
        echo '<tr><td>'.$row['LastName'].', '.$row['FirstName'].'</td>';
        echo '<td>'.AppUtility::tzdate("F j, Y, g:i a", $row['lastview']).'</td></tr>';
    }
    echo '</tbody></table>';
}
echo '<p class="small">'._('Note: Only the most recent thread view per person is shown').'</p>';

?>
