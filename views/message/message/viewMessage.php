    <?php
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
        <a class="btn btn-primary btn-sm">Reply</a>&nbsp;
        <a class="btn btn-primary btn-sm">Mark Unread</a>&nbsp;
        <a class="btn btn-primary btn-sm btn-danger">Delete</a>&nbsp;
        <a href="">View Conversation</a> |
        <a href="">Gradebook</a>
     </div>

