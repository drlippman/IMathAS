<?php

/**
 * @package   yii2-krajee-base
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @version   1.7.4
 */

namespace kartik\base;

/**
 * Base asset bundle for all widgets
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class AssetBundle extends \yii\web\AssetBundle
{
    const EMPTY_ASSET = 'N0/@$$3T$';
    const EMPTY_PATH = 'N0/P@T#';
    const KRAJEE_ASSET = 'K3/@$$3T$';
    const KRAJEE_PATH = 'K3/P@T#';
    
    public $js = self::KRAJEE_ASSET;
    public $css = self::KRAJEE_ASSET;
    public $sourcePath = self::KRAJEE_PATH;    
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        if ($this->js === self::KRAJEE_ASSET) {
            $this->js = [];
        }
        if ($this->css === self::KRAJEE_ASSET) {
            $this->css = [];
        }
        if ($this->sourcePath === self::KRAJEE_PATH) {
            $this->sourcePath = null;
        }
    }
    
    /**
     * Set up CSS and JS asset arrays based on the base-file names
     *
     * @param string $type whether 'css' or 'js'
     * @param array  $files the list of 'css' or 'js' basefile names
     */
    protected function setupAssets($type, $files = [])
    {
        if ($this->$type === self::KRAJEE_ASSET) {
            $srcFiles = [];
            $minFiles = [];
            foreach ($files as $file) {
                $srcFiles[] = "{$file}.{$type}";
                $minFiles[] = "{$file}.min.{$type}";
            }
            $this->$type = YII_DEBUG ? $srcFiles : $minFiles;
        } elseif ($this->$type === self::EMPTY_ASSET) {
            $this->$type = [];
        }
    }

    /**
     * Sets the source path if empty
     *
     * @param string $path the path to be set
     */
    protected function setSourcePath($path)
    {
        if ($this->sourcePath === self::KRAJEE_PATH) {
            $this->sourcePath = $path;
        } elseif ($this->sourcePath === self::EMPTY_PATH) {
            $this->sourcePath = null;
        }
    }
}
