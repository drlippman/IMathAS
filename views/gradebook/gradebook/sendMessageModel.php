<?php
use app\components\AppUtility;

$flexwidth = true;
$nologo = true;
$firstname = $receiverInformation['FirstName'];
$lastname = $receiverInformation['LastName'];
$email = $receiverInformation['email'];
$useeditor = "message";
if ($params['sendtype'] == 'msg') {
    echo '<h2>New Message</h2>';
    $to = "$lastname, $firstname";
} else if ($params['sendtype'] == 'email') {
    echo '<h2>New Email</h2>';
    $to = "$lastname, $firstname ($email)";
}
if (isset($_GET['quoteq'])) {

    require("../assessment/displayq2.php");
    $parts = explode('-', $_GET['quoteq']);
    $message = displayq($parts[0], $parts[1], $parts[2], false, false, 0, true);
    $message = printfilter(forcefiltergraph($message));
    $message = preg_replace('/(`[^`]*`)/', "<span class=\"AM\">$1</span>", $message);

    $message = '<p> </p><br/><hr/>' . $message;
    $courseid = $course->id;
    if (isset($parts[3])) {  //sending to instructor
        $query = "SELECT name FROM imas_assessments WHERE id='" . intval($parts[3]) . "'";
        $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
        $title = 'Question about #' . ($parts[0] + 1) . ' in ' . str_replace('"', '&quot;', mysql_result($result, 0, 0));
        if ($_GET['to'] == 'instr') {
            unset($_GET['to']);
            $msgset = 1; //force instructor only list
        }
    } else {
        $title = '';
    }
} else if (isset($params['title'])) {
    $title = $params['title'];
    $message = '';
    $courseid = $course->id;
} else {
    $title = '';
    $message = '';
    $courseid = $course->id;
}
?>
    <form method="post" action="send-message-model?cid=<?php echo $course->id; ?>">
        <input type="hidden" name="sendto" value="<?php echo $params['sendto'] ?>"/>
        <input type="hidden" name="sendtype" value="<?php echo $params['sendtype']; ?>"/>
        <?php
        echo "To: $to<br/>\n";
        echo "Subject: <input type=text size=50 name=subject id=subject value=\"$title\"><br/>\n";
        echo "Message: <div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
        echo htmlentities($message);
        echo "</textarea></div><br/>\n";

        if ($params['sendtype'] == 'msg') {
            ?>
            <div class="submit"><input type="submit" value="<?php AppUtility::t('Send Message') ?>"></div>
        <?php
        } else if ($params['sendtype'] == 'email') { ?>
            <div class="submit"><input type="submit" value="<?php AppUtility::t('Send Email') ?>"></div>
        <?php
        }
        ?>
    </form>
<?php
exit;
//}
