<?php
use app\components\AppUtility;

$flexwidth = true;
$nologo = true;
$firstname = $receiverInformation['FirstName'];
$lastname = $receiverInformation['LastName'];
$email = $receiverInformation['email'];
$useeditor = "message";
global $temp;
if ($params['sendtype'] == 'msg') {
    $this->title = 'New Message';
    $saveButton = 'Send Message';
    $to = "$lastname, $firstname";
} else if ($params['sendtype'] == 'email') {
    $this->title = 'New Email';
    $saveButton = 'Send Email';
    $to = "$lastname, $firstname ($email)";
}

?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form method="post" id="form-id" action="send-message-model?cid=<?php echo $course->id; ?>">
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn">
            <button class="btn btn-primary pull-right page-settings" type="button" onclick="submitForm()" id="addNewThread" value="Submit">
                <i class="fa fa-share header-right-btn"></i><?php echo $saveButton; ?></button>
        </div>
    </div>
</div>
<?php
if (isset($_GET['quoteq'])) {

    require("../assessment/displayq2.php");
    $parts = explode('-', $_GET['quoteq']);
    $message = displayq($parts[0], $parts[1], $parts[2], false, false, 0, true);
    echo $temp;
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
  <div class="tab-content shadowBox non-nav-tab-item padding-top-thirty padding-bottom-thirty">
        <input type="hidden" name="sendto" value="<?php echo $params['sendto'] ?>"/>
        <input type="hidden" name="sendtype" value="<?php echo $params['sendtype']; ?>"/>
      <div class="col-sm-12">
         <span class='col-sm-1'><?php AppUtility::t('To')?></span>
        <span class='col-sm-4'>
            <?php echo $to ?>
            </span>
            </div>
      <br><br>
      <div class="col-sm-12">
      <span class="col-sm-1"><?php AppUtility::t('Subject')?> </span>
         <span class="col-sm-4"> <input type=text size=50 class="form-control subject" name=subject id=subject value="<?php echo $title ?>"><br/></span>
          </div>
      <br><br>
      <div class="col-sm-12">
          <span class="col-sm-1">
         <?php AppUtility::t('Message') ?>
              </span>
          <span class="col-sm-11"><div class=editor><textarea  id=message name=message style="width: 100%;" rows=12 cols=20></span>
                  <?php echo htmlentities($message); ?>
         </textarea>
      </div>
      </div>
</div>
    </form>