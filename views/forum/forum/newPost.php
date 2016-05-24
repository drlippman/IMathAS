<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\helpers\Html;
$this->title = AppUtility::t('New Posts',false );
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>
<div class="item-detail-header">
    <?php if($users->rights == 100 || $users->rights == 20) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]);
    } elseif($users->rights == 10)
    {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/index?cid=' . $course->id]]);
    }?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="tab-content shadowBox ">
    <br><p></p>
    <div class="align-new-post-table">
        <div class="overflow-x-auto col-sm-12">
        <?php if($threadArray){?>
        <?php echo '<table id="myTable" class="table table-bordered table-striped table-hover data-table"><thead><tr>
        <th class="width-twenty-seven-per text-align-center ">'.'Forum'.'</th>
        <th class="width-twenty-seven-per text-align-center">'.'Topic'.'</th>
        <th class="width-twenty-seven-per text-align-center">'.'Started By'.'</th>
        <th class="width-nineteen-per text-align-center">'.'Last Post Date'.'</th>';?>
        <?php
        foreach($threadArray as $data)
        {
            if(isset($data['subject']))
            {
                echo '</thead><tr>
                <td class="text-align-center word-break-break-all"><a href="search-forum?cid='.$course->id.'">'.$data['forumName'].'</a></td>
                <td class="text-align-center word-break-break-all"><a href="post?courseid='.$course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumiddata'].'">'.$data['subject'].'</a></td>
                <td class="text-align-center">'.$data['name'].'</td>
                <td class="text-align-center">'.$data['postdate'].'</td>
                </tr>';
            }
        }
        echo '</tbody></table>';

        ?>
        <?php }else{?>
        <div class="no-notifictn">
       <h4 style="text-align: center">No New Notifications</h4>
        </div>
        <?php }?>
    </div>
    </div>
</div>
