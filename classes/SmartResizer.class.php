<?php
/**
 * Resize and cache images according to "img src" tags.
 * Optionally add a link to a lightbox view of the original image,
 * unless the image is already part of an existing link.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2021 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.12
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
    /** Flag to indicate if a lightbox link to the original shouold be added.
     * @var boolean */
    private $add_lightbox = 1;

    /** Site url value to add into the relative image urls.
     * Needed if the page will be hosted elsewhere, e.g. email messages.
     * @var string */
    private $site_url = '';

    /** Full path to the resized image file.
     * May be used later if needed.
     * @var string */
    private $tn_path = '';

    /** Templates registered with the smart resizer.
     * @var array */
    private static $_templates = array(
        'article' => array(
            'story_introtext',
            'story_introtext_only',
            'story_bodytext_only',
            'story_text_no_br',
        ),
        'featuredarticle' => array(
            'story_introtext',
            'story_introtext_only',
            'story_bodytext_only',
            'story_text_no_br',
        ),
    );


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
            self::create()->convert($var);
            //self::Text($var);
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
        self::create()->convert($origtxt);
    }


    /**
     * Resize images found in a text string.
     * Updates the content in-place.
     *
     * @uses    Image::reDim()
     * @param   string  &$origtxt   Text string to update
     * @return  void
     */
    public function convert(&$origtxt)
    {
        global $_CONF;

        // Convert the utf-8 characters to HTML entities to avoid
        // tripping up the DOM functions.
        $page = mb_convert_encoding($origtxt, 'HTML-ENTITIES', 'UTF-8');

        libxml_use_internal_errors(true);
        $dom= new \DOMDocument();
        // Load html with opening and closing tags to prevent DOM from breaking
        // any initial tags. The html tags will be removed at the end.
        $status = $dom->loadHTML(
            '<html>' . $page. '</html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Check that the document was loaded and there were no errors
        //$x = libxml_get_errors();
        if ($status === false) {// || count($x) > 0) {
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
            if (strpos($tag, 'nosmartresize') > 0) {
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
            $src_path = $_CONF['path_html'] . $src;

            // Split up the path to extract the filename and extension
            $fparts = pathinfo($url_parts['path']);
            if (!isset($fparts['extension'])) {
                continue;
            }
            $extension = $fparts['extension'];

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
                    if (empty($parts[0]) || !isset($parts[1])) {
                        // May happen if there's a trailing `;` in the style
                        continue;
                    }
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

            // Now we know that the image is to be resized.
            // Create the thumbnail path in "/thumbs" under the original dir
            $thumb_path = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $_CONF['path_html'] . $fparts['dirname'] . '/thumbs'
            );
            if (!is_dir($thumb_path)) {
                @mkdir($thumb_path);
            }

            // Create the thumbnail url, relative, and from it create the path
            $thumb_url = $fparts['dirname'] . '/thumbs/' . $fparts['filename']
                . "_{$d_width}_{$d_height}." . $fparts['extension'];
            $this->tn_path = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $_CONF['path_html'] . $thumb_url
            );
            if (!file_exists($this->tn_path)) {
                // Thumb doesn't already exist, create it
                $res = Image::reSize($src_path, $this->tn_path, $d_width, $d_height);
            }

            if (!file_exists($this->tn_path)) {
                // Something went wrong if the thumb file still doesn't exist.
                COM_errorLog("LGLIb\SmartResizer: could not create {$this->tn_path}");
                continue;
            }

            // Set the new image source to the thumbnail URL.
            // This is the only thing changed in the img tag.
            $img->setAttribute('src', $this->site_url . $thumb_url);

            // Now, create a lightbox link to the original image,
            // but only if the image isn't already part of a link tag and
            // the add_lightbox flag is set (default).
            if (!$is_linked && $this->add_lightbox) {
                $div = $dom->createElement('div');
                $div->setAttribute('uk-lightbox', '');
                // Create a "a" element for the link
                $a = $dom->createElement('a');

                // Get the alt tag to use as a title
                $title = $img->getAttribute('alt');
                if (!empty($title)) {
                    $a->setAttribute('title', $title);
                }

                $a->setAttribute('href', $this->site_url . $src);
                $a->setAttribute('data-uk-lightbox', "{group:'article'}");
                $a->appendChild($img->cloneNode());
                // replace img with the wrapper that is holding the <img>
                $div->appendChild($a);
                $img->parentNode->replaceChild($div, $img);
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


    /**
     * Enable or disable lightbox links.
     *
     * @param   boolean $flag   True to link to lightbox, False otherwise
     * @return  object  $this
     */
    public function withLightbox($flag)
    {
        $this->add_lightbox = $flag ? 1 : 0;
        return $this;
    }


    /**
     * Set whether to include full url, including host, im image tags.
     *
     * @param   boolean $flag   True to use full URL, False to not
     * @return  object  $this
     */
    public function withFullUrl($flag)
    {
        global $_CONF;

        $this->site_url = $flag ? $_CONF['site_url'] : '';
        return $this;
    }


    /**
     * Factory method to create a SmartResizer instance.
     *
     * @return  object  $this
     */
    public static function create()
    {
        return new self;
    }


    /**
     * Register a template name with the smart resizer.
     *
     * @param   string  $tpl_name   Template name
     * @param   string|array    $varnames   One or an array of variable names
     */
    public static function registerTemplate($tpl_name, $varnames)
    {
        if (!is_array($varnames)) {
            $varnames = array($varnames);
        }

        if (isset(self::$_templates[$tpl_name])) {
            self::$_templates[$tpl_name] = array_unique(
                array_merge(
                    self::$_templates[$tpl_name],
                    $varnames
                )
            );
        } else {
            self::$_templates[$tpl_name] = $varnames;
        }
        return true;
    }


    /**
     * Get the template variable names that should have images resized.
     *
     * @param   string  $tpl_name   Registered template name
     * @return  array       Array of template variable names
     */
    public static function getTemplateVars($tpl_name)
    {
        if (
            isset(self::$_templates[$tpl_name]) &&
            is_array(self::$_templates[$tpl_name])
        ) {
            return self::$_templates[$tpl_name];
        } else {
            return array();
        }
    }

}
