<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/imascore.css?ver=030415',
        'css/default.css?v=121713',
        'css/site.css',
        'css/jquery-ui.css',
        'css/jquery-ui.min.css',
        'css/jquery-ui.structure.css',
        'css/jquery-ui.structure.min.css',
        'css/handheld.css',
    ];
    public $jsOptions = array(
        'position' => \yii\web\View::POS_HEAD
    );
    public $js = [
        'js/jquery.min.js',
//        'js/general.js',
        'js/mathjax/MathJax.js?config=AM_HTMLorMML',
        'js/ASCIIsvg_min.js?ver=012314',
        'js/mathgraphcheck.js?v=021215',
        'js/jquery-ui.js',
        'js/jquery-ui.min.js',

    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}