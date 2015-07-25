<?php
use app\components\AppUtility;
?>

<div class="master-footer">
<div class="row">
    <div class="col-md-8 row-alignment">
            <div class="col-md-1 footer-alignment">
                <a href="#"><?php AppUtility::t('Support')?></a>
            </div>

            <div class="col-md-1 footer-alignment">
                <a href="#"><?php AppUtility::t('About')?></a>
            </div>

            <div class="col-md-1 footer-alignment">
                <a href="#"><?php AppUtility::t('Contact') ?></a>
            </div>

            <div class="col-md-1 footer-alignment">
                <a href="#"><?php AppUtility::t('PrivacyPolicy')?></a>
            </div>
    </div>
    <div class="row">
        <div class="col-md-4 footer-brand">

                <b><?php AppUtility::t('MyOpenMath')?></b>

        </div>
    </div>
</div>
</div>