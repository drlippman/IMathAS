<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
?>
<div id="wikiName">
    <h2><?php echo $wiki->name; ?></h2>
</div>

<p><span id="revisioninfo">Revision 0</span></p>
<div class="editor">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
           class="btn btn-primary btn-sm">Edit this page</a></span><BR><BR>
    <div id="wikicontent" class="wikicontent">

    </div>
</div>