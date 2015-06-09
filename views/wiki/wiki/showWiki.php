<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
//AppUtility::dump($userData->FirstName);?>
<div id="wikiName">
    <h2><?php echo $wiki->name; ?></h2>
</div>

<p><span id="revisioninfo">Revision 0</span></p>
<div class="editor">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
           class="btn btn-primary btn-sm">Edit this page</a></span>
        <?php foreach($wikiRevisionData as $key => $singleWikiRevision) { ?>
            <div id="wikicontent" class="wikicontent">
                <?php $text = $singleWikiRevision->revision;
                      $time = $singleWikiRevision->time;?>
                <?php if($text != null) {?>
                    <p><?php echo $text; ?></p>
                    <?php if (strlen($text)>6 && substr($text,0,6)=='**wver') {
                        $wikiver = substr($text,6,strpos($text,'**',6)-6);
                        $text = substr($text,strpos($text,'**',6)+2);
                        } else {
                        $wikiver = 1;
                        }
                        $lastedittime = AppUtility::tzdate("F j, Y, g:i a", $time);
                        $lasteditedby = $userData->FirstName.', '.$userData->LastName;
                   ?>
                <?php }?>
            </div>
    <?php }?>
</div>