<?php
namespace app\components;
//IMathAS:  User image upload function
//(c) 20009 David Lippman

use yii\base\Component;

$curdir = rtrim(dirname(__FILE__), '/\\');

// $image is $_FILES[ <image name> ]
// $imageId is the id used in a database or wherever for this image
// $thumbWidth and $thumbHeight are desired dimensions for the thumbnail

class UserPics extends Component
{
    public static function processImage( $image, $imageId, $thumbWidth, $thumbHeight )
    {
        $type = $image[ 'type' ];
        $curdir = rtrim(dirname(__FILE__), '/\\');
        $galleryPath = AppUtility::getHomeURL().AppConstant::UPLOAD_DIRECTORY;

        if ( strpos( $type, 'image/' ) === FALSE )
        { // not an image
            return FALSE;
        }
        $type = str_replace( 'image/', '', $type );
        if ($type=='pjpeg') { //stupid IE6
            $type = 'jpeg';
        }
        if ($type!='jpeg' && $type!='png' && $type!='gif') {
            //invalid image type
            return FALSE;
        }
        $createFunc = 'imagecreatefrom' . $type;
        $im = @$createFunc( $image[ 'tmp_name' ] );

        if (!$im) {
            return;
        }
        $size = getimagesize( $image[ 'tmp_name' ] );
        $w = $size[ 0 ];
        $h = $size[ 1 ];

        // create thumbnail
        $tw = $thumbWidth;
        $th = $thumbHeight;


        if ( $w/$h > $tw/$th )
        { // wider
            $imT = imagecreatetruecolor( $tw, $th );
            $tmpw = $w*($th/$h);
            $temp = imagecreatetruecolor( $tmpw, $th );
            imagecopyresampled( $temp, $im, 0, 0, 0, 0, $tmpw, $th, $w, $h ); // resize to width
            imagecopyresampled( $imT, $temp, 0, 0, $tmpw/2-$tw/2,0, $tw, $th, $tw, $th ); // crop
            imagedestroy( $temp );
        }else
        {
        // taller
            /* crops
        $imT = imagecreatetruecolor( $tw, $th );
        $tmph = $h*($tw/$w );
            $temp = imagecreatetruecolor( $tw, $tmph );
            imagecopyresampled( $temp, $im, 0, 0, 0, 0, $tw, $tmph, $w, $h ); // resize to height
            imagecopyresampled( $imT, $temp, 0, 0, 0, $tmph/2-$th/2, $tw, $th, $tw, $th ); // crop
        imagedestroy( $temp );
        */
        //nocrop version
        $tmpw = $w*($th/$h);
        $imT = imagecreatetruecolor( $tmpw, $th );
        imagecopyresampled( $imT, $im, 0, 0, 0, 0, $tmpw, $th, $w, $h ); // resize to width
        }

        // save the image
        imagejpeg( $imT, $galleryPath .$imageId . '.jpg', 100);
       filehandler::relocatecoursefileifneeded($galleryPath .$imageId . '.jpg',$imageId . '.jpg');
    }
}
?>
