<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/6/15
 * Time: 8:20 PM
 */

namespace app\models;


use app\models\_base\BaseImasWikiViews;

class WikiView extends BaseImasWikiViews
{
    public static function getWikiViewTotalData($wikiId, $userId)
    {
        return WikiView::findAll(['wikiid' => $wikiId, 'userid' => $userId]);
    }
} 