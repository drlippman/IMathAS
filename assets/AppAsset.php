<?php
namespace app\assets;

use app\components\AppConstant;
use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\web\YiiAsset',
    ];
    public $css = [
        'css/imascore.css?ver=20',
        'css/jquery-ui.min.css?ver=20',
        'css/jquery-ui.structure.min.css?ver=20',
        'css/site.css?ver=20',
        'css/default.css?ver=20',
        'css/font-awesome.min.css?ver=20'
    ];
    public $jsOptions = array(
        'position' => \yii\web\View::POS_HEAD
    );
    public $js = [
        'js/mathjax/MathJax.js?config=AM_HTMLorMML',
        'js/ASCIIsvg_min.js?ver=20',
        'js/mathgraphcheck.js?ver=20',
        'js/jquery-ui.min.js?ver=20',
        'js/common.js?ver=20',
    ];
}