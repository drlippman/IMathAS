    <?php
    use app\components\AppUtility;
    $this->title = 'Messages';
    $this->params['breadcrumbs'][] = $this->title;
    echo $this->render('../../instructor/instructor/_toolbarTeacher');
?>
    <div id="headerviewmsg">
        <h2>Message</h2>
    </div>
    <div>
    <table class= msg-view >
        <tbody>
        <tr>
            <td><b>From:</b></td>
            <td><?php echo ucfirst($fromUser->FirstName).' '.ucfirst($fromUser->LastName) ?></td>
        </tr>
        <tr>
            <td><b>Sent:</b></td>
            <td><?php echo date('M d, o g:i a' ,$messages->senddate) ?></td>
        </tr>
        <tr>
            <td><b>Subject:</b></td>
            <td><?php echo $messages->title ?></td>
        </tr>
        </tbody>
    </table>
    </div>
    <div>
        <pre>
            <?php echo $messages->message ?>
         </pre>
    </div>
    <div >
        <?php if(Yii::$app->user->identity->getId()==$messages->msgfrom){echo "have a good day !";}
        else{
            echo "<div>
                     <a class='btn btn-primary' > Mark Unread </a >
            </div>";
        } ?>
        <a href = "<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id='.$messages->id);?>" class="btn btn-primary " > Reply</a >&nbsp;
        <a class="btn btn-primary " > Mark Unread </a >&nbsp;
        <a class="btn btn-primary  btn-danger" > Delete</a >&nbsp;
        <a href = "<?php echo AppUtility::getURLFromHome('message', 'message/view-conversation?id='.$messages->id.'&baseid='.$messages->baseid);?>" > View Conversation </a > |
        <a href = "" > Gradebook</a >

     </div>

