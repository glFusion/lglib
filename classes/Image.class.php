<?php
/**
 * Class to handle images.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2012-2022 Lee Garner <lee@leegarner.com>
 * @package    lglib
 * @version    1.1.1
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace LGLib;
use glFusion\Log\Log;


/**
 *  Image-handling class
 */
class Image
{
    /**
     * Calculate dimensions to resize an image, preserving the aspect ratio.
     *
     * @param   string  $origpath   Original image path
     * @param   integer $width      New width, in pixels
     * @param   integer $height     New height, in pixels
     * @param   boolean $expand     True to allow expanding the image
     * @return  mixed       array of dimensions, or false on error
     */
    public static function reDim($orig_path, $width=0, $height=0, $expand=false)
    {
        $dimensions = @getimagesize($orig_path);
        if ($dimensions === false) {
            return false;
        }

        $s_width = $dimensions[0];
        $s_height = $dimensions[1];
        $mime_type = $dimensions['mime'];

        // get both sizefactors that would resize one dimension correctly
        if ($width > 0 && ($s_width > $width || $expand)) {
            $sizefactor_w = (double)($width / $s_width);
        } else {
            $sizefactor_w = 1;
        }
        if ($height > 0 && ($s_height > $height || $expand)) {
            $sizefactor_h = (double)($height / $s_height);
        }else {
            $sizefactor_h = 1;
        }

        // Use the smaller factor to stay within the parameters
        $sizefactor = min($sizefactor_w, $sizefactor_h);

        $newwidth = (int)($s_width * $sizefactor);
        $newheight = (int)($s_height * $sizefactor);

        return array(
            's_width'   => $s_width,
            's_height'  => $s_height,
            'd_width'   => $newwidth,
            'd_height'  => $newheight,
            'mime'      => $mime_type,
        );
    }


    /**
     * Resize an image to the specified dimensions into the new location.
     * At least one of $newWidth or $newHeight must be specified.
     *
     * @param   string  $src        Full source image path
     * @param   string  $dst        Full destination image path
     * @param   integer $newWidth   New width, in pixels
     * @param   integer $newHeight  New height, in pixels
     * @param   boolean $expand     True to allow expanding the image
     * @return  mixed   Array of new width,height if successful, NULL if failed
     */
    public static function reSize(string $src, string $dst, int $newWidth=0, int $newHeight=0, bool $expand=false) : ?array
    {
        // Calculate the new dimensions
        $A = self::reDim($src, $newWidth, $newHeight, $expand);
        if ($A === false) {
            COM_errorLog(__CLASS__ . ": Invalid image $src");
            return NULL;
        }

        $dimensions = @getimagesize($src);
        if ($dimensions === false) {
            return NULL;
        }
        if ($dimensions['mime'] == 'image/png') {
            $result = self::resizeGD(
                $src, $dst,
                $A['s_width'], $A['s_height'],
                $A['d_width'], $A['d_height'],
                $dimensions['mime']
            );
        } else {
            // Returns an array, with [0] either true/false and [1]
            // containing a message.
            if (function_exists('_img_resizeImage')) {
                $result = _img_resizeImage(
                    $src, $dst,
                    $A['s_height'], $A['s_width'],
                    $A['d_height'], $A['d_width'],
                    $A['mime']
                );
            } else {
                $result = array(false);
            }
        }
        if ($result[0] === false) {
            Log::write('system', Log::ERROR, __METHOD__ . ": Failed to convert $src ({$A['s_height']} x {$A['s_width']}) to $dst ($newHeight x $newWidth)");
            return NULL;
        } else {
            return $A;
        }
    }


    /**
     * Use GD instead of the default image lib to preserve transparency.
     *
     * @param   string  $type       Either 'thumb' or 'disp'
     * @param   integer $newWidth   New width, in pixels
     * @param   integer $newHeight  New height, in pixels
     * @param   boolean $expand     True to allow expanding the image
     * @return  object  $this
     */
    public static function resizeGD(string $src, string $dst, int $s_width, int $s_height, int $d_width, int $d_height, string $mime) : array
    {
        global $_CONF;

        // just an indexed array to mimic _img_resizeimage()
        $retval = array(false, '');

        if (empty($src) || empty($dst)) {
            return $retval;
        }

        // If the file already exists, just return the current info.
        if (is_file($dst)) {
            $retval[0] = true;
            return $retval;
        }

        $JpegQuality = 85;
        if ($_CONF['debug_image_upload']) {
            Log::write('system', Log::ERROR,
                __METHOD__ . '(): ' .
                ": Resizing using GD2: Src = " . $src . " mimetype = " . $mime
            );
        }
        switch ($mime) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $image = @imagecreatefromjpeg($src);
            break;
        case 'image/png' :
            $image = @imagecreatefrompng($src);
            break;
        case 'image/bmp' :
            $image = @imagecreatefromwbmp($src);
            break;
        case 'image/gif' :
            $image = @imagecreatefromgif($src);
            break;
        case 'image/x-targa' :
        case 'image/tga' :
            $retval[1] = "IMG_resizeImage: TGA files not supported by GD2 Libs";
            Log::write('system', Log::ERROR, $retval[1]);
            return $retval;
        default :
            $retval[1] = "IMG_resizeImage: GD2 only supports JPG, PNG and GIF image types.";
            Log::write('system', Log::ERROR, $retval[1]);
            return $this;
        }

        if (!$image) {
            $retval[1] = "IMG_resizeImage: GD Libs failed to create working image.";
            Log::write('system', Log::ERROR, $retval[1]);
            return $retval;
        }

        $newimage = imagecreatetruecolor($d_width, $d_height);
        imagealphablending($newimage, false);
        imagesavealpha($newimage, true);
        imagecopyresampled(
            $newimage, $image,
            0, 0,
            0, 0,
            $d_width, $d_height,
            $s_width, $s_height
        );

        switch ($mime) {
        case 'image/jpeg' :
        case 'image/jpg' :
            imagejpeg($newimage, $dst, $JpegQuality);
            break;
        case 'image/png' :
            $pngQuality = ceil(intval(($JpegQuality / 100) + 8));
            imagepng($newimage, $dst, $pngQuality);
            break;
        case 'image/bmp' :
            imagewbmp($newimage, $dst);
            break;
        case 'image/gif' :
            imagegif($newimage, $dst);
            break;
        }
        imagedestroy($newimage);
        $retval[0] = true;
        return $retval;
    }

}
