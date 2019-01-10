<?php
/**
 * Resize and cache images according to "img src" tags.
 * Based on the SmartResizer plugin for Joomla.
 *
 * @see https://extensions.joomla.org/extension/smartresizer/
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
 * @package    lglib
 * @version    1.0.5
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace LGLib;

/**
 * SmartResizer class.
 */
class SmartResizer {

    /**
     * Resize images found in a template.
     * Updates the template content in-place.
     *
     * @uses    self::Text()
     * @param   object  &$template  Template Object
     * @param   string  $valname    Template value to update
     * @return  void
     */
    public static function Template(&$template, $valname)
    {
        if (isset($template->varvals[$valname])) {
            self::Text($template->varvals[$valname]);
        }
    }


    /**
     * Resize images found in a text string.
     * Updates the content in-place.
     *
     * @uses    self::getDimFromTag()
     * @uses    Image::reDim()
     * @param   string  &$origtxt   Text string to update
     * @return  void
     */
    public static function Text(&$origtxt)
    {
        global $_CONF;

        $runword='';    // Holdover from Joomla. Trigger to skip processing
        $regex_img = "|<[\s\v]*img[\s\v]([^>]*".$runword."[^>]*)>|Ui";
        preg_match_all( $regex_img, $origtxt, $matches_img);
        $count_img = count($matches_img[0]);

        for ($i = 0; $i < $count_img; $i++) {
            // Skip processing if tagged not to resize
            if (strpos($matches_img[0][$i], 'nosmartresize'))
                continue;

            // Skip processing if an invalid image is found
            if (!@$matches_img[1][$i])
                continue;

            // Initialize vars. $inline_params contains the entire img tag
            $image_width = 0;
            $image_height = 0;
            $inline_params = $matches_img[1][$i];
            $src = array();

            // Get the "src=" part of the tag.
            preg_match( "#src=\"(.*?)\"#si", $inline_params, $src );
            if (isset($src[1])) {
                $src = trim($src[1]);
            } else {
                // no img src value found
                continue;
            }

            // Split up the src, check if it's a relative or remote URL
            $url_parts = parse_url($src);
            if (isset($url_parts['host'])) {
                continue;  // don't handle remote images
            }

            // Split up the path to extract the filename and extension
            $fparts = pathinfo($url_parts['path']);
            $extension = $fparts['extension'];
            /*switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;
            case 'png':
                $mime = 'image/png';
                break;
            case 'bmp':
                $mime = 'image/bmp';
                break;
            case 'gif':
                $mime = 'image/gif';
                break;
            }*/

            // Create the thumbnail path in "/thumbs" under the original dir
            $thumb_path = str_replace('/', DIRECTORY_SEPARATOR,
                $_CONF['path_html'] . $fparts['dirname'] . '/thumbs');
            $src_path = $_CONF['path_html'] . $src;
            if (!is_dir($thumb_path)) {
                COM_errorLog("Resizer: Attempting to create $thumb_path");
                @mkdir($thumb_path);
            }

            // Get the height and width parameters, if supplied.
            // If one is missing, reDim() will resize based on the supplied one.
            // If both are missing, then continue since there's no resizing to do.
            $d_width = self::getDimFromTag('width', $inline_params);	
            $d_height= self::getDimFromTag('height', $inline_params);	
            if (empty($d_width) && empty($d_height)) {
                continue;
            }

            $A = Image::reDim($src_path, $d_width, $d_height);
            if ($A === false) continue;
            $s_width = $A['s_width'];
            $s_height = $A['s_height'];
            $d_width = $A['d_width'];
            $d_height = $A['d_height'];
            $mime = $A['mime'];

            //list($s_width,$s_height, $d_width, $d_height) =
            //        self::reDim($src_path, $d_width, $d_height);
            if ($s_width < $d_width && $s_height < $d_height) {
                // Don't scale the image up, just use the original.
                continue;
            }
            // Create the thumbnail url, relative, and from it create the path
            $thumb_url = $fparts['dirname'] . '/thumbs/' . $fparts['filename'] . "_{$d_width}_{$d_height}." . $fparts['extension'];
            $thumb_path = str_replace('/', DIRECTORY_SEPARATOR,
                     $_CONF['path_html'] . $thumb_url);
            if (!file_exists($thumb_path)) {
                // Thumb doesn't already exist, create it
                if (function_exists(_img_resizeImage)) {
                    $result = _img_resizeImage($src_path, $thumb_path,
                            $s_height, $s_width,
                            $d_height, $d_width, $mime);
                }
            }
            if (!file_exists($thumb_path)) {
                // Something went wrong if the thumb file still doesn't exist.
                continue;
            }

            // Get the alt tag to use as a title
    		preg_match("#alt=\"(.*?)\"#si", $inline_params, $title);
	    	if (isset($title[1])) {
                $title = ' title="' . trim($title[1]) . '" ';
            } else {
                $title = '';
            }

    		$text = str_replace($src, $thumb_url, $matches_img[0][$i]);
	        $text = '<a href="' . $src .
                '" data-uk-lightbox="{group:\'article\'}" " width="' .
                $s_width . '" height="' . $s_height . '" ' . $title .
                '>'.$text.'</a>';
	        $origtxt = str_replace($matches_img[0][$i], $text, $origtxt);
        }
    }


    /**
     * Get the dimension parameters from the image source tag.
     * First check for style tag, then look for width/height tags.
     *
     * @param   string  $type   "width" or "height"
     * @param   string  $content    Image tag content
     * @return  string      Size from tag, or empty string if not found
     */
    private static function getDimFromTag($type, $content)
    {
        $size = array();
        preg_match("#[\s\;\"]{$type}:(.*?)px*[\s\;\"]#si", $content, $size);
        if (isset($size[1])) {
            $retval = trim($size[1]);
        } else {
            // style="width:..." tag not found, look for simple "width=xx"
    		preg_match("#{$type}=\"(.*?)\"#si", $content, $size);
    		if (isset($size[1])) {
                $retval = trim($size[1]);
            } else {
                $retval = '';
            }
        }
        return $retval;
    }
}

?>
