<?php
use \app\components\AppUtility;
if (isset($params['id']))
{
    $this->title = 'Edit Tool';
    $this->params['breadcrumbs'] = $this->title;
}
else{
    $this->title = 'External Tools';
    $this->params['breadcrumbs'] = $this->title;
}
?>

<div class="item-detail-header">
    <?php
        if($isTeacher){
            echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]);
            if (isset($params['ltfrom'])) {

                echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name, 'Modify Linked Text'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id, AppUtility::getHomeURL().'add-linked-text?cid='.$course->id.'&amp;id='.$params['ltfrom']], 'page_title' => $this->title]);
            }
        } else {
            if (isset($params['id'])) {
                echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home','Admin', 'External Tools'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index', AppUtility::getHomeURL().'admin/admin/external-tool?cid='.$courseId.$ltfrom], 'page_title' => $this->title]);
            } else{
                echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home','Admin'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index'], 'page_title' => $this->title]);
            }
        }
    ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <br>
<?php
    if (!(isset($teacherId)) && $myRights < 75) {
        $err = "You need to log in as a teacher to access this page";
    } elseif (isset($params['cid']) && $params['cid']=="admin" && $myRights < 75) {
        $err = "You need to log in as an admin to access this page";
    } elseif (!(isset($params['cid'])) && $myRights < 75) {
        $err = "Please access this page from the menu links only.";
    }

        if (isset($params['delete']))
        {
            echo " &gt; <a href=".AppUtility::getURLFromHome('admin', 'admin/external-tool?cid'.$courseId.$ltfrom).">External Tools</a>
             &gt; Delete Tool</div>";
            echo "<h2>Delete Tool</h2>";
            $query = "SELECT name FROM imas_external_tools WHERE id='{$_GET['id']}'";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            $name = mysql_result($result,0,0);

            echo '<p>Are you SURE you want to delete the tool <b>'.$name.'</b>?  Doing so will break ALL placements of this tool.</p>';
            echo '<form method="post" action="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$_GET['id'].'&amp;delete=true">';
            echo '<input type=submit value="Yes, I\'m Sure">';
            echo '<input type=button value="Nevermind" class="secondarybtn" onclick="window.location=\'externaltools.php?cid='.$cid.'\'">';
            echo '</form>';

        } else if (isset($params['id'])) {
        ?>
            <div class="col-lg-2">Tool Name:</div>
            <div class="col-lg-10">
                <input class="input-item-title" size="100" type="text" name="tname" value="<?php echo $name;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2">Launch URL:</div>
            <div class="col-lg-10">
                <input type="text" size="40" name="url" value="<?php echo $url;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2">Key:</div>
            <div class="col-lg-10">
                <input type="text" size="40" name="key" value="<?php echo $key;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2">Secret:</div>
            <div class="col-lg-10">
                <input type="password" size="40" name="secret" value="<?php echo $secret;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2">Custom Parameters:</div>
            <div class="col-lg-8">
                    <textarea rows="2" cols="30" name="custom"><?php echo $custom;?></textarea>
             </div>
            <br class="form" /><br/>

            <div class="col-lg-2">Privacy:</div>
            <div class="col-lg-8">
                <input type="checkbox" name="privname" value="1" <?php if (($privacy&1)==1) echo 'checked="checked"';?> /> Send name<br/>
                <input type="checkbox" name="privemail" value="2" <?php if (($privacy&2)==2) echo 'checked="checked"';?> /> Send email
            </div>
            <br class="form" /><br/>
        <?php
        if ($isAdmin) {
            echo '<div class="col-lg-2">Scope of tool:</div>
            <div class="col-lg-8">';
                echo '<input type="radio" name="scope" value="0" '. (($grp==0)?'checked="checked"':'') . '> System-wide<br/>';
                echo '<input type="radio" name="scope" value="1" '. (($grp>0)?'checked="checked"':'') . '> Group';
                echo '</div>
            <br class="form" /><br/>';
        }
        echo '<div class="submit"><input type="submit" value="Save"></div>';
        echo '</form>';

        } else {
            if ($isAdmin) {
                echo '<p><b>System and Group Tools</b></p>';
            } else if ($isGrpAdmin) {
                echo '<p><b>Group Tools</b></p>';
            } else {
                echo '<p><b>Course Tools</b></p>';
            }
            echo '<ul class="nomark">';
            if (count($resultFirst) == 0) {
                echo '<li>No tools</li>';
            } else {
                foreach($resultFirst as $key => $row)
                 {
                    echo '<li>'.$row['nm'];
                    if ($isAdmin) {
                        if ($row['name'] == null) {
                            echo ' (System-wide)';
                        } else {
                            echo ' (for group '.$row['name'].')';
                        }
                    }
                    echo ' <a href='.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$row['id']).'>Edit</a> ';
                    echo '| <a href='.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$row['id'].'&amp;delete=ask').'>Delete</a> ';
                    echo '</li>';
                }
            }
            echo '</ul>';
            echo '<p><a href="'.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId. '&amp;id=new').'">Add a Tool</a></p>';

        } ?>

</div>