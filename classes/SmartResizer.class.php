<?php
/**
 * Resize and cache images according to "img src" tags.
 * Ignore images that are already part of an existing link.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2019 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.9
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace LGLib;

/**
 * SmartResizer class.
 */
class SmartResizer
{

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

        $dom= new \DOMDocument();
        $dom->loadHTML($origtxt);
        $xpath = new \DOMXPath($dom);
        $images = $xpath->query("//img");
        foreach ($images as $img) {
            // save the entire tag <img src="..." class=... />
            $tag = $img->ownerDocument->saveXML($img);
            if (strpos($tag, 'noresize') > 0) {
                continue;
            }

            $is_linked = false;
            $p = $img;
            while ($p->parentNode !== NULL) {
                if ($p->tagName == 'a') {
                    $is_linked = true;
                    break;
                }
                $p = $p->parentNode;
            }

            // Split up the src, check if it's a relative or remote URL
            $src = $img->getAttribute('src');
            $url_parts = parse_url($src);
            if (isset($url_parts['host'])) {
                continue;  // don't handle remote images
            }

            // Split up the path to extract the filename and extension
            $fparts = pathinfo($url_parts['path']);
            $extension = $fparts['extension'];

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
            $d_width = 0;
            $d_height = 0;
            if ($img->hasAttribute('style')) {
                $attribs = explode(';', $img->getAttribute('style'));
                foreach ($attribs as $attr) {
                    $parts = explode(':', $attr);
                    if ($parts[0] == 'width') {
                        $d_width = (int)$parts[1];
                    } elseif ($parts[0] == 'height') {
                        $d_height = (int)$parts[1];
                    }
                }
            }
            // Now look for old-style tags if the dimenstions aren't already included
            if ($d_width == 0) {
                $val = $img->getAttribute('width');
                if (!empty($val)) {
                    $d_width = $val;
                }
            }
            if ($d_height== 0) {
                $val = $img->getAttribute('height');
                if (!empty($val)) {
                    $d_height = $val;
                }
            }
            // If there isn't at least one, then give up, no resizing to do
            if (empty($d_width) && empty($d_height)) {
                continue;
            }

            // Determine the new width and height that will fit within the specified
            // tags while preservint the aspect ratio.
            // Have to do this here since Image::ReSize() won't be called if the
            // thumbnail exists, but we still need the correct image dimensions.
            $A = Image::reDim($src_path, $d_width, $d_height);
            if ($A === false) continue;
            $s_width = $A['s_width'];
            $s_height = $A['s_height'];
            $d_width = $A['d_width'];
            $d_height = $A['d_height'];
            $mime = $A['mime'];

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
                $res = Image::ReSize($src_path, $thumb_path, $d_width, $d_height);
            }

            if (!file_exists($thumb_path)) {
                // Something went wrong if the thumb file still doesn't exist.
                continue;
            }

            // Set the new image source to the thumbnail URL.
            // This is the only thing changed in the img tag.
            $img->setAttribute('src', $thumb_url);

            // Now, create a link to the original image, but only if the image
            // isn't already part of a link tag.
            if (!$is_linked) {
                // Create a "a" element for the link
                $a = $dom->createElement('a');

                // Get the alt tag to use as a title
                $title = $img->getAttribute('alt');
                if (!empty($title)) {
                    $a->setAttribute('title', $title);
                }

                $a->setAttribute('href', $src);
                $a->setAttribute('data-uk-lightbox', "{group:'article'}");
                $a->setAttribute('style', 'width:' . $A['s_width'] . 'px;height:' . $A['s_height'] . 'px');
                $a->appendChild($img->cloneNode());

                // replace img with the wrapper that is holding the <img>
                $img->parentNode->replaceChild($a, $img);
            }
        }

        // Finally, save the new document into the original text
        $origtxt = $dom->saveHTML();
    }

}

?>
