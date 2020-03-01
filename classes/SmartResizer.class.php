<?php
/**
 * Resize and cache images according to "img src" tags.
 * Ignore images that are already part of an existing link.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.10
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
        $var = $template->get_var($valname);
        if (!empty($var)) {
            self::Text($var);
            $template->set_var($valname, $var);
        }
    }


    /**
     * Resize images found in a text string.
     * Updates the content in-place.
     *
     * @uses    Image::reDim()
     * @param   string  &$origtxt   Text string to update
     * @return  void
     */
    public static function Text(&$origtxt)
    {
        global $_CONF;

        libxml_use_internal_errors(true);
        $dom= new \DOMDocument();
        // Load html with opening and closing tags to prevent DOM from breaking
        // any initial tags. The html tags will be removed at the end.
        $status = $dom->loadHTML(
            '<html>' . $origtxt . '</html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        // Check that the document was loaded and there were no errors
        $x = libxml_get_errors();
        if ($status === false || count($x) > 0) {
            // Couldn't load the document, return without changing
            return;
        }

        $images = $dom->getElementsByTagName('img');
        if (count($images) < 1) {
            // No images, nothing to do
            return;
        }

        // Get the site hostname to compare with image urls.
        // If the scheme or host differs then treat as a remote image
        // and do not process.
        $site_url_parts = parse_url($_CONF['site_url']);
        $site_host = $site_url_parts['host'];
        $site_scheme = $site_url_parts['scheme'];
        $have_changes = false;
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
                if (
                    $url_parts['host'] != $site_host ||
                    $url_parts['scheme'] != $site_scheme
                ) {
                    continue;  // don't handle remote images
                } else {
                    $src = str_replace($_CONF['site_url'], '', $src);
                }
            }

            // Split up the path to extract the filename and extension
            $fparts = pathinfo($url_parts['path']);
            $extension = $fparts['extension'];

            // Create the thumbnail path in "/thumbs" under the original dir
            $thumb_path = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $_CONF['path_html'] . $fparts['dirname'] . '/thumbs'
            );

            $src_path = $_CONF['path_html'] . $src;
            if (!is_dir($thumb_path)) {
                COM_errorLog("Resizer: Attempting to create $thumb_path");
                @mkdir($thumb_path);
            }

            // Get the height and width parameters, if supplied.
            // If one is missing, reDim() will resize based on the supplied one.
            // If both are missing, then continue since there's no resizing
            // to do.
            $d_width = 0;
            $d_height = 0;
            if ($img->hasAttribute('style')) {
                $attribs = explode(';', $img->getAttribute('style'));
                foreach ($attribs as $attr) {
                    $parts = explode(':', $attr);
                    $parts[0] = trim($parts[0]);
                    $parts[1] = trim($parts[1]);
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
                    $d_height = (int)$val;
                }
            }
            if (empty($d_width) && empty($d_height)) {
                // No dimension attributes specified, don't do anything
                continue;
            } elseif (empty($d_width) || empty($d_height)) {
                // One dimension provided, use reDim to get the other
                $A = Image::reDim($src_path, $d_width, $d_height);
                if ($A === false) continue;
                $s_width = $A['s_width'];
                $s_height = $A['s_height'];
                $d_width = $A['d_width'];
                $d_height = $A['d_height'];
            }
            // Else, use the provided $d_height and $d_width dimensions

            // Create the thumbnail url, relative, and from it create the path
            $thumb_url = $fparts['dirname'] . '/thumbs/' . $fparts['filename'] . "_{$d_width}_{$d_height}." . $fparts['extension'];
            $thumb_path = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $_CONF['path_html'] . $thumb_url
            );
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
                $a->appendChild($img->cloneNode());

                // replace img with the wrapper that is holding the <img>
                $img->parentNode->replaceChild($a, $img);
            }
            $have_changes = true;   // Note that a change was made
        }

        // Finally, save the new document into the original text.
        // Strip the bogus html tags that were added earlier to keep DOM
        // from breaking.
        if ($have_changes) {
            $origtxt = str_replace(
                array('<html>','</html>') ,
                '' ,
                $dom->saveHTML()
            );
        }
    }

}

?>
