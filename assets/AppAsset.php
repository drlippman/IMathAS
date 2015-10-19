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
        'css/imascore.css?ver=19',
        'css/jquery-ui.min.css?ver=19',
        'css/jquery-ui.structure.min.css?ver=19',
        'css/site.css?ver=19',
        'css/default.css?ver=19',
        'css/font-awesome.min.css?ver=19'
    ];
    public $jsOptions = array(
        'position' => \yii\web\View::POS_HEAD
    );
    public $js = [
        'js/mathjax/MathJax.js?config=AM_HTMLorMML',
        'js/ASCIIsvg_min.js?ver=19',
        'js/mathgraphcheck.js?ver=19',
        'js/jquery-ui.min.js?ver=19',
        'js/common.js?ver=19',
    ];
}