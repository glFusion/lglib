<?php
/**
 * glFusion API functions for the lgLib plugin.
 * This plugin's job is simply to load javascript and css files that are
 * required by other plugins.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2021 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.13
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

spl_autoload_register(function($class)
{
    $prefix = 'LGLib\\';
    if (strpos($class, $prefix) === 0) {
        // does the class use the namespace prefix?
        $len = strlen($prefix);
        // base directory for the namespace prefix
        $base_dir = __DIR__ . '/classes/';

        // get the relative class name, following the prefix.
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $path = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';
        if (file_exists($path)) {
            include $path;
        }
        return;
    }
    switch ($class) {
    case 'FPDF':
        $path = __DIR__ . '/classes/pdf/fpdf/fpdf.php';
        break;
    case 'FPDF2File':
    case 'PDF_HTML2File':
        $path = __DIR__ . '/classes/pdf/' . $class . '.php';
        break;
    default:
        $path = __DIR__ . '/classes/' . $class . '.class.php';
        break;
    }
    if (file_exists($path)) {
        // Using "include_once" to provide compatibility with plugins that
        // still call the USES_ functions to load classes.
        include_once $path;
    }
});
use LGLib\Config;

/** Additional config items and table definitions are added here */
require_once __DIR__ . '/lglib.php';

// Define the global flags here, before any functions are called.
// This will force any older plugins that don't require this plugin to NOT load
// their versions of the CSS and JS.
define('HAVE_CALENDAR_CSS', Config::PI_NAME);
define('HAVE_SLIMBOX_CSS', Config::PI_NAME);
define('HAVE_CALENDAR_JS', Config::PI_NAME);
define('HAVE_SLIMBOX_JS', Config::PI_NAME);

// Use SESSION or DB for message storage.  DB requires the creation of the
// lglib tables.
//define('LGLIB_MSG_STORE', 'session');
define('LGLIB_MSG_STORE', 'db');        // Required for inter-user messages
define('LGLIB_MSG_VAR', 'lgmessages');  // session variable name

// Define the beginning of week for the Date_Calc class.
// The default is "1" for "Monday"
if (!defined('DATE_CALC_BEGIN_WEEKDAY')) {
    global $_CONF;
    switch ($_CONF['week_start']) {
    case 'Mon':
        // week begins on Monday
        define('DATE_CALC_BEGIN_WEEKDAY', 1);
        break;
    case 'Sun':
    default:
        // week begins on Sunday
        define('DATE_CALC_BEGIN_WEEKDAY', 0);
        break;
    }
}
if (!defined('DATE_CALC_FORMAT')) {
    define('DATE_CALC_FORMAT', '%Y-%m-%d');
}

// Pseudo-global variables accessible through LGLIB_setGlobal()
// and LGLIB_getGlobal()
$_LGLIB_GLOBALS = array();

/**
 * Include the plugin-specific language file.
 * Looks for the site language first, then defaults to english if nothing else found.
 */
$langpath = __DIR__ . '/language';
if (is_file("$langpath/{$_CONF['language']}.php")) {
    $langfile = "{$_CONF['language']}.php";
} else {
    $langfile = 'english_utf-8.php';
}
require_once "$langpath/$langfile";

if (is_file("$langpath/custom/$langfile")) {
    /** Include any custom language file, if found */
    include_once "$langpath/custom/$langfile";
}

/** Include the user-supplied configuration file, if any */
if (is_file(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/vendor/autoload.php';



/**
 * Include the PEAR Date Calc class in the root namespace.
 */
function USES_lglib_class_datecalc()
{
    require_once __DIR__ . '/classes/DateCalc.class.php';
}

/**
 * Load the nameparser class.
 */
function USES_lglib_class_nameparser()
{
    // Include a shim class during the transition to namespaces.
    require_once __DIR__ . '/classes/NameParser_tmp.class.php';
    //require_once __DIR__ . '/classes/HumanNameParser.class.php';
}

/**
 * Load the image-handling class.
 */
function USES_lglib_class_image()
{
    require_once __DIR__ . '/classes/image.class.php';
}

/** Database backup class */
function USES_lglib_class_dbbackup()
{   require_once __DIR__ . '/classes/dbBackup.class.php';    }

/**
 * Import PDF and other classes.
 */
function USES_lglib_classes()
{
}
require_once __DIR__ . '/vendor/autoload.php';


/**
 * Import the PDF utility class.
 * If a derived class file is requested, e.g "PDF_HTML", then load that file.
 * otherwise, load the base FPDF class file.
 *
 * @param   string  $file   Optional child class filename
 */
function USES_lglib_class_fpdf($file='')
{
    if ($file != '') {
        // if a file is specified make sure it's got a "php" extension
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext != 'php') {
            $file .= '.php';
        }
        require_once __DIR__ . '/classes/pdf/' . $file;
    } else {
        // just load the base FPDF class
        require_once __DIR__ . '/classes/pdf/fpdf/fpdf.php';
    }
}


/**
 * Import the TCPDF class.
 * Kept for backward compatibility.
 * @deprecated v1.0.9
 */
function USES_lglib_class_tcpdf()
{
    USES_lglib_classes();
}


/**
 * Import the HTML2PDF class.
 * Kept for backward compatibility.
 * Class is now in vendor/spipu/html2pdf
 * @deprecated v1.0.9
 */
function USES_lglib_class_html2pdf()
{
    USES_lglib_classes();
}


/**
 * Get the currently installed version.
 *
 * @return  string  version number
 */
function plugin_chkVersion_lglib()
{
    return Config::get('pi_version');
}


/**
 * Puts an option for the plugin in the command and control block.
 * Not used in glFusion versions > 1.6.0 since the database backup function
 * has been updated in the main DB Backup option.
 *
 * @return  array  Array of (plugin name, admin url, url of plugin icon)
 */
function plugin_cclabel_lglib()
{
    if (SEC_inGroup('Root')) {
        return array(
            Config::get('pi_display_name'),
            Config::get('admin_url') . '/index.php',
            plugin_geticon_lglib(),
        );
    } else {
        return '';
    }
}


/**
 * When this plugin is disabled, notify other plugins.
 *
 * @param   boolean $enable     True if enabling, False if disabling
 */
function plugin_enablestatechange_lglib($enable)
{
    if (!$enable) {
        PLG_itemDeleted('*', 'lglib');
    }
}


/**
 * Adds the plugin to the Admin menu.
 *
 * @return  array   Array(pi_name, admin url, # of items) or '' for none.
 */
 function plugin_getadminoption_lglib()
{
    if (SEC_inGroup('Root')) {
        $retval = array(
            Config::get('pi_display_name'),
            Config::get('admin_url') . '/index.php',
            0,
        );
    } else {
        $retval = '';
    }
    return $retval;
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_lglib()
{
    return Config::get('url') . '/images/lglib.png';
}


/**
 * Execute additional functions when COM_siteHeader() is called.
 * - Set up the session var for the smart resizer
 * - Set the default position for popup messages
 *
 * @return  string      Empty string, no actual header code is used.
 */
function plugin_getheadercode_lglib() : string
{
    global $_CONF, $_SYSTEM;

    // Session variables are used to pass path info to the TimThumb image resizer.
    // Since img.php doesn't load lib-common.php (it would take forever to load
    // images), these values have to get to it somehow.
    // Yes, these can also be overridden via config.php
    // The calling plugin needs to set $_SESSION['<pi_name>_origpath']
    SESS_setVar('lglib', array(
        'origpath'      => $_CONF['path_html'] . 'images',
        'cache_dir'     => $_CONF['path'] . 'data/' . Config::get('pi_name'),
        'image_lib'     => $_CONF['image_lib'],
        'path_to_mogrify' => $_CONF['path_to_mogrify'],
    ));

    // Set default message location to top center unless otherwise set
    if (!isset($_SYSTEM['alert_position'])) {
        $_SYSTEM['alert_position'] = 'top-center';
    }

    return '';
}


/**
 * Get additional CSS into the site header.
 * Allows for custom CSS to be placed by the site admin.
 *
 * @return  array   List of paths to CSS files
 */
function plugin_getheadercss_lglib()
{
    global $_CONF, $_SYSTEM;

    // glFusion 1.4 uses different paths to css cache than 1.5.0, so need
    // different versions of css files.
    $prefix = GVERSION < '1.5.0' ? '140/' : '150/';

    $styles = array();

    $files = array(
        'style.css',
        // Include the popup calendar CSS.
        "{$prefix}calendar-" . Config::get('cal_style') . ".css",
        'datetime1/jquery-ui-timepicker-addon.css',
    );

    // Either jQuery or Mootools version
    if (!isset($_SYSTEM['disable_mootools']) || $_SYSTEM['disable_mootools'] == false) {
        $files[] = "{$prefix}slimbox.css";
    }
    if (!isset($_SYSTEM['disable_jquery_slimbox']) || $_SYSTEM['disable_jquery_slimbox'] == false) {
        $files[] = "{$prefix}slimbox2.css";
    }

    // When looking for CSS files, we'll check in this order:
    //  1. layout/plugin/custom
    //  2. layout/plugin
    //  3. plugin/custom
    //  4. plugin (default distribution version)
    $pi_css = __DIR__ . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/" . Config::get('pi_name') . "/";
    foreach ($files as $file) {
        if (@file_exists($layout_css . 'custom/' . $file)) {
            $styles[] = $layout_css . 'custom/' . $file;
        } elseif (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } elseif (@file_exists($pi_css . $file)) {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
 * Gets the list of javascript files required for this plugin.
 *
 * @return  array       List of javascript files to load
 */
function plugin_getheaderjs_lglib()
{
    global $_CONF, $_SYSTEM;

    $js_dir = __DIR__ . '/js';

    // For the datepicker popup, check that the ISO language file exists.
    // Default to English if it doesn't.
    $iso_lang = $_CONF['iso_lang'];
    if (!is_file("{$js_dir}/calendar/lang/calendar-{$iso_lang}.js")) {
        $iso_lang = 'en';
    }

    $files = array(
        // datepicker
        "$js_dir/calendar/calendar.js",
        "$js_dir/calendar/lang/calendar-{$iso_lang}.js",
        "$js_dir/calendar/calendar-setup.js",
        "$js_dir/datetime1/jquery-ui-timepicker-addon.js",
        "$js_dir/util.js",
    );

    // Either jQuery or Mootools
    if (!isset($_SYSTEM['disable_mootools']) || $_SYSTEM['disable_mootools'] == false) {
        $files[] = "$js_dir/slimbox.js";
    }

    if (!isset($_SYSTEM['disable_jquery_slimbox']) || $_SYSTEM['disable_jquery_slimbox'] == false) {
        if (Config::get('slimbox_autoactivation')) {
            // no 'rel="slimbox"' tags needed
            $files[] = "$js_dir/autoactivation.js";
        } else {
            // require 'rel="slimbox"' tags
            $files[] = "$js_dir/relactivation.js";
        }
    }

    return $files;
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @see     plugin_getconfigelementhelp_lglib()
 * @param   string  $file       HTML file
 * @param   string  $pi_name    Name of plugin, default "lglib"
 * @param   string  $doclang    Documentation language, default English
 * @return  s tring              URL to document directory or file
 */
function LGLIB_getDocURL($file='', $pi_name='lglib', $doclang='english')
{
    global $_CONF;

    $doc_path = "{$_CONF['path_html']}$pi_name/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        $file .= '.html';
        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/$file";
    } else {
        // just getting the config document directory
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/";
    }
    return $doc_url;
}


/**
 * Get the URL to the configuration help file.
 *
 * @param   string  $option     Config option selected
 * @param   string  $doclang    Document language
 * @return  string              URL to document
 */
function plugin_getconfigelementhelp_lglib($option, $doclang='english')
{
    $doc_url = LGLIB_getDocURL('config.html', Config::get('pi_name'), $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }

    return array($doc_url, 0);
}


/**
 * Upgrade the plugin to the currently-installed version.
 *
 * @return  boolean     True if successful, False otherwise
 */
function plugin_upgrade_lglib()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return LGLIB_do_upgrade();
}


/**
 * Set template variables.
 * Puts any messages to be displayed into the lglib_messags
 * template variable, if used, or appends them to the info_block
 * variable that should be part of every header.thtml template.
 *
 * @param   string  $templatename   Name of template, e.g. 'header'
 * @param   ref     $template       Reference to actual template
 */
function plugin_templateSetVars_lglib($templatename, &$template)
{
    static $messages = NULL;

    // Get messages to display to the user and put them in the template
    // This can be called more than once per page display, so load
    // the messages only once.
    if ($messages === NULL) {
        $messages = LGLib\Message::showAll();
    }
    if ($messages != '') {
        if (Config::get('use_lglib_messages')) {
            $template->set_var('lglib_messages', $messages);
        } elseif ($templatename == 'footer') {
            // Have to do this during the 'footer' template to make sure it's
            // only done once.
            $info_block = $template->get_var('info_block') . $messages;
            $template->set_var('info_block', $info_block);
        }
    }

    // Resize images referenced in the templates if so configured.
    /*if (Config::get('enable_smartresizer')) {
        $varnames = LGLib\SmartResizer::getTemplateVars($templatename);
        foreach ($varnames as $varname) {
            LGLib\SmartResizer::Template($template, $varname);
        }
    }*/
}


/**
 * Register a template name and one or more variables for SmartResizer.
 *
 * @param   string  $tpl_name   Template name
 * @param   string|array    $varnames   One or an array of variable names
 */
function plugin_registerSmartResizer_lglib($tpl_name, $varnames)
{
    LGLib\SmartResizer::registerTemplate($tpl_name, $varnames);
}


/**
 * Perform filtering and updating on final output before display.
 *
 * @param   string  $output     HTML output to process
 * @param   string  $plugin     Invoking plugin
 * @return  string              Updated $output
 */
function plugin_outputfilter_lglib($output, $plugin='')
{
    // Resize images referenced in the templates if so configured
    if (Config::get('enable_smartresizer')) {
       LGLib\SmartResizer::Text($output);
    }
    return $output;
}


/**
 * Return true to enable service functions.
 * Allows LGLIB_invokeService() to be deprecated in favor of
 * PLG_invokeSerivce().
 *
 * @return  boolean     True to enable web services
 */
function plugin_wsEnabled_lglib()
{
    return true;
}


/**
 * Invoke a service in another plugin.
 * This is our own version of PLG_invokeService() so the other plugins
 * don't have to enable web services.  This is not used by this plugin,
 * but is available for other plugins.
 *
 * Service function names are formatted as "service_SERVICENAME_PLUGIN()"
 *
 * @param   string  $type       Plugin name (PLUGIN)
 * @param   string  $action     Plugin service (SERVICENAME)
 * @param   array   $args       Array of arguments to pass to the plugin
 * @param   mixed   &$output    Pointer to output values
 * @param   mixed   &$svc_msg   Pointer to service message
 * @return  integer Return value, see lib-plugins.php
 */
function LGLIB_invokeService($type, $action, $args, &$output, &$svc_msg)
{
    $retval = PLG_RET_ERROR;

    $output  = '';
    $svc_msg = '';
    // Check if the plugin type and action are valid
    $function = 'service_' . $action . '_' . $type;
    if (function_exists($function)) {
        if (is_array($args) && !isset($args['gl_svc'])) {
            $args['gl_svc'] = false;
        }
        $retval = $function($args, $output, $svc_msg);
    }
    return $retval;
}


/**
 * Store a message in the database that can be retrieved later by another plugin.
 * This provides a more flexible method for showing popup messages than the numbered-message method.
 *
 * @param   array|string    $args   Message to be displayed, or argument array
 * @param   string  $title      Optional title
 * @param   boolean $persist    True if the message should persist onscreen
 * @param   string  $expires    SQL-formatted expiration datetime
 * @param   string  $pi_code    Name of plugin storing the message
 * @param   integer $uid        ID of the user to view the message
 * @param   boolean $use_sess_id    True to use session ID to retrieve
 */
function LGLIB_storeMessage($args, $title='', $persist=false, $expires = '',
    $pi_code = '', $uid = 0, $use_sess_id = true)
{
    global $LANG_LGLIB, $_CONF, $_USER;

    // Convert to a single "args" array argument, but maintain backward
    // compatibility for some time.
    $level = 'info';
    $persist = false;
    $msg = '';
    $title = '';
    $expires = '';
    $pi_code = '';
    $uid = '';
    if (is_array($args)) {
        if (isset($args['message'])) {
            $msg = $args['message'];
        } elseif (isset($args['msg'])) {
            $msg = $args['msg'];
        }
        if (isset($args['title']) && empty($title)) {
            $title = $args['title'];
        }
        if (isset($args['persist'])) {
            $persist = $args['persist'] == true ? true : false;
        }
        if (isset($args['expires']) && empty($expires)) {
            $expires = $args['expires'];
        }
        if (isset($args['pi_code']) && empty($pi_code)) {
            $pi_code = $args['pi_code'];
        } elseif (isset($args['plugin'])) {
            $pi_code = $args['plugin'];
        }
        if (isset($args['uid']) && empty($uid)) {
            $uid = (int)$args['uid'];
        }
        if (isset($args['use_sess_id'])) {
            $use_sess_id = $args['use_sess_id'] == true ? true : false;
        }
        if (isset($args['level'])) {
            $level = $args['level'];
        }
    } else {
        // parameters passed in using the old format
        $msg = $args;
    }
    (new LGLib\Message())
        ->withTitle($title)
        ->withMessage($msg)
        ->withLevel($level)
        ->withUid($uid)
        ->withPersists($persist)
        ->withPiCode($pi_code)
        ->withExpires($expires)
        ->store();
}


/**
 * Delete a message from the message queue.
 *
 * @param   integer $uid    Target user ID
 * @param   string  $pi_code    Plugin-supplied message identifier
 */
function LGLIB_deleteMessage($uid, $pi_code)
{
    LGLib\Message::deleteOne($uid, $pi_code);
}


/**
 * Check if a specific message exists.
 * Looks for the user ID, session ID and plugin code.
 *
 * @param   integer $uid        Target User Id
 * @param   string  $sess_id    Session ID
 * @param   string  $pi_code    Plugin name & code value
 * @return  string      Message text, empty if not found
 */
function LGLIB_messageExists($uid, $pi_code='')
{
    global $_TABLES;

    return (new LGLib\Message)
        ->withUid($uid)
        ->withPlugin($pi_code)
        ->exists();
}


/**
 * Show a popup login form.
 *
 * @return  string  HTML for popup form.
 */
function LGLIB_loginPopup()
{
    return '<div id="modal" style="border:3px solid black; background-color:#9999ff; padding:25px; font-size:150%; text-align:center; display:none;">' .
        LGLIB_loginform() .
        '</div>';
}


/**
 * Create a login form.
 *
 * @return  string      Login form
 */
function LGLIB_loginform()
{
    global $_CONF, $LANG04;

    $options = array(
        'hide_forgotpw_link' => false,
        'form_action'       => $_CONF['site_url'] . '/users.php',
        'title'             => $LANG04[65],
        'message'           => $LANG04[66],
    );
    return SEC_loginForm($options);
}


/**
 * A generic function to store pseudo-global variables.
 *
 * @param   string  $name   Variable name
 * @param   mixed   $value  Value to store
 * @param   boolean $clobber    False to avoid overwriting
 */
function LGLIB_setGlobal($name, $value, $clobber=true)
{
    global $_LGLIB_GLOBALS;
    if ($clobber || !isset($_LGLIB_GLOBALS[$name])) {
        $_LGLIB_GLOBALS[$name] = $value;
    }
}


/**
 * Retrieve a pseudo-global variable.
 *
 * @param   string  $name   Name of variable
 * @param   boolean $remove True to unset the variable, False to leave it
 * @return  mixed           Session variable contents
 */
function LGLIB_getGlobal($name, $remove=false)
{
    global $_LGLIB_GLOBALS;
    if (isset($_LGLIB_GLOBALS[$name])) {
        $retval = $_LGLIB_GLOBALS[$name];
        if ($remove) unset($_LGLIB_GLOBALS[$name]);
    } else {
        $retval = NULL;
    }
    return $retval;
}


/**
 * Convert a string to title case, ignoring small words.
 * e.g. "a test of a title function" becomes "A Test of a Title Function".
 *
 * @param   string  $str    String to be converted
 * @return  string          Converted title string
 */
function LGLIB_titleCase($str)
{
    global $LANG_LGLIB;

    $words = explode(' ', $str);
    foreach ($words as $key => $word) {
        if ($key == 0 or (!in_array($word, $LANG_LGLIB['smallwords']))) {
            $words[$key] = ucwords($word);
        }
    }
    return implode(' ', $words);
}


/**
 * Get the URL to an image.
 * New version just calls service_imageurl_lglib() and returns the url
 * for backward compatibility.
 *
 * Sample usage:
 *  `echo '<img src=" . LGLIB_ImageUrl(/some/file/path.jpg, 800, 600) . '">';`
 *
 * @param   string  $orig_path  Full path to the original image
 * @param   integer $width      Image width
 * @param   integer $height     Image height
 * @param   mixed   $rnd        Optional random ID to force image recreation
 * @return  string              Full URL to the image, empty on error
 */
function LGLIB_ImageUrl($orig_path, $width=0, $height=0, $rnd = 0)
{
    $args = array(
        'filepath'  => $orig_path,
        'width'     => $width,
        'height'    => $height,
        'rnd'       => $rnd,
    );
    $url = '';
    $status = service_imageurl_lglib($args, $output, $svc_msg);
    if ($status == PLG_RET_OK) {
        return $output['url'];
    } else {
        return '';
    }
}


/**
 * Obfuscate a string (e.g. email address) so it can be displayed safely.
 * Borrowed from Dokuwiki's configuration.
 *
 * @param   string  $str    String to obfuscate
 * @return  string          Obfuscated email address.
 */
function LGLIB_obfuscate($str)
{
    $encode = '';
    for ($x=0; $x < strlen($str); $x++) {
        $encode .= '&#x' . bin2hex($str[$x]).';';
    }
    return $encode;
}


/**
 * Push a job onto the job queue.
 * Called by plugins to add a job to the queue.
 * $pi_name and $job_name are required, $params may be a string or
 * an array. If it's an array it is converted to JSON before storage.
 *
 * @param  string  $pi_name    Name of plugin to execute the job
 * @param  string  $job_name   Name of job to be run
 * @param  mixed   $params     String or Array of parameters
 * @return bookean     True on success, False on failure.
 */
function LGLIB_pushJob($pi_name, $job_name, $params='')
{
    return LGLib\JobQueue::push($pi_name, $job_name, $params);

    if (is_array($params)) {
        $json_params = json_encode($params);
        if ($json_params === false) return false;
    } else {
        $json_params = $params;
    }

    $pi_name = DB_escapeString($pi_name);
    $job_name = DB_escapeString($job_name);
    $params = DB_escapeString($json_params);
    $sql = "INSERT INTO gl_lglib_jobqueue (pi_name, submitted, jobname, params)
            VALUES ('$pi_name', UNIX_TIMESTAMP(), '$job_name', '$params')";
    DB_query($sql, false);
    if (DB_error()) {
        return false;
    } else {
        return true;
    }
}


/**
 * Get the image info for a resized image.
 *
 * Args array is expected to ontain values for 'filepath', 'width', 'height',
 * and an optional random number for force image regeneration.
 *
 * @uses    Image::reSize()
 * @param   array   $args       Array of item information
 * @param   array   &$output    Pointer to output array
 * @param   array   &$svc_msg   Unused
 * @return  integer             Status code
 */
function service_imageurl_lglib($args, &$output, &$svc_msg)
{
    global $_CONF;

    // Initialize return value to empty URL.
    // Maybe change to a stock "missing" image in the future.
    $output = array();

    // Sanitize arguments
    $width = isset($args['width']) ? (int)$args['width'] : 0;
    $height = isset($args['height']) ? (int)$args['height'] : 0;
    $rnd = isset($args['rnd']) ? (int)$args['rnd'] : 0;
    $orig_path = isset($args['filepath']) ? $args['filepath'] : '';
    $expand = isset($args['expand']) ? (int)$args['expand'] : 0;
    $force = isset($args['force']) && $args['force'] ? 1 : 0;

    // If original image doesn't exist, return empty url,
    // don't bother continuing.
    if (empty($orig_path) || !is_file($orig_path)) {
        return PLG_RET_ERROR;
    }

    // Separate the path and create a MD5 hash of its path and size
    $path_parts = pathinfo($orig_path);
    $disp_fname = md5("$rnd-$orig_path-$width-$height") . '.' .
            $path_parts['extension'];

    // Image path portion of url
    $img_path = Config::get('pi_name') . '/' . Config::get('img_disp_relpath') .
            '/' . $disp_fname[0];

    // Image url relative to display base url
    $disp_relurl = $img_path . '/' . $disp_fname;

    // Display image full path, for checking file_exists and storing resized
    $disp_fullpath = $_CONF['path_html'] . $disp_relurl;

    $maxage = (int)Config::get('img_cache_maxage');
    $interval = (int)Config::get('img_cache_interval');
    if ($maxage > 0 && $interval > 0) {
        // Clean up old files
        // Get absolute path for flag file and finding old cache files
        $img_path = $_CONF['path_html'] . $img_path;

        $lastCleanFile = $img_path . '/.lastclean.touch';
        if (!is_file($lastCleanFile)) {
            @touch($lastCleanFile);
        }
        if (@filemtime($lastCleanFile) < ((time() - $interval) * 60)) {
            $files = glob($img_path . '/*');
            if ($files) {
                $timeAgo = time() - ($maxage * 86400);
                foreach ($files as $file) {
                    if (@filemtime($file) < $timeAgo) {
                        @unlink($file);
                    }
                }
            }
        }
    }

    // If the file isn't found, try to create it.
    if (!is_file($disp_fullpath) || $force) {
        $A = LGLib\Image::reSize($orig_path, $disp_fullpath, $width, $height, $expand);
        if ($A) {
            $width = $A['d_width'];
            $height = $A['d_height'];
            $mime_type = $A['mime'];
        }
    } else {
        // Have an existing file, need to get the dimensions
        $A = getimagesize($disp_fullpath);
        $width = $A[0];
        $height = $A[1];
        $mime_type = $A['mime'];
        @touch($disp_fullpath);     // update timestamp to indicate usage
    }

    // If the file exists, return the full url.
    // If not, there's a problem and the empty url will be returned.
    if (is_file($disp_fullpath)) {
        $output = array(
            'url'   => $_CONF['site_url'] . '/' . $disp_relurl,
            'path'  => $disp_fullpath,
            'width' => $width,
            'height' => $height,
            'mime'  => $mime_type,
        );
        return PLG_RET_OK;
    } else {
        $output = array(
            'url'   => '',
            'width' => 0,
            'height' => 0,
            'mime'  => '',
            'path'  => '',
        );
        return PLG_RET_ERROR;
    }
}


/**
 * Get an icon from the CSS icon set, either UIkit or FontAwesome.
 *
 * @param   string  $name   Base name of icon, e.g. "edit" for "uk-icon-edit"
 * @param   string  $style  Style name from local CSS
 * @return  string      Class names for icon
 */
function LGLIB_getIcon($name, $style='info')
{
    if ($style != '')
        $style = ' lglib-icon-' . $style;

    if (Config::get('_is_uikit')) {
        return ' uk-icon-' . $name . $style;
    } else {
        return 'fa fa-' . $name . $style;
    }
}


/**
 * Get the colorpicker JS and CSS.
 * There are default values for each parameter.
 * Sample parameters: array(
 *       'fg_id'     => forground input field ID
 *       'fg_color'  => current foreground color, including '#'
 *       'bg_id'     => background input field ID
 *       'bg_color'  => current background color, including '#'
 *       'sample_id' => ID of sample display field
 *
 * @param  array   $A  Optional array of field attributes
 * @return string      HTML to load JS and CSS for the colorpicker
 */
function LGLIB_colorPicker($A = array())
{
    static $need_js = true;

    // Must be an array, if supplied
    if (!is_array($A)) return '';

    $T = new \Template(__DIR__ . '/templates');
    $T->set_file('picker', 'colorpicker.thtml');

    // Set other config variables. Assume "true" unless "false" is received
    $vars = array(
        'showPaletteOnly',
        'togglePaletteOnly',
        'showInput',
        'allowEmpty',
    );
    foreach ($vars as $var) {
        $T->set_var($var, isset($A[$var]) && !$A[$var] ? 'false' : 'true');
    }

    $T->set_var(array(
        'fg_id' => isset($A['fg_id']) ? $A['fg_id'] : 'fgcolor',
        'fg_color' => isset($A['fg_color']) ? $A['fg_color'] : '#000000',
        'bg_id' => isset($A['bg_id']) ? $A['bg_id'] : 'bgcolor',
        'bg_color' => isset($A['bg_color']) ? $A['bg_color'] : '#FFFFFF',
        'sample_id' => isset($A['sample_id']) ? $A['sample_id'] : 'sample',
        'include_js' => $need_js,
    ) );
    $T->parse('output', 'picker');
    $need_js = false;
    return $T->finish($T->get_var('output'));
}


/**
 * Get an array variable, first checking whether it's set.
 *
 * @since   v1.0.8
 * @param   array   $A          Array where the variable should be
 * @param   string  $key        Name of array element
 * @param   string  $type       Type of return value
 * @param   mixed   $default    Default value, if key is not set
 * @return      Array element cast to $type, or NULL if not set
 */
function LGLIB_getVar($A, $key, $type = 'string', $default = NULL)
{
    // If the key is not set, return the default
    if (!is_array($A) || !isset($A[$key])) {
        return $default;
    } else {
        $val = $A[$key];
    }

    // Cast the value according to the specified type
    switch ($type) {
    case 'int':
    case 'integer':
        $val = (int)$val;
        break;
    case 'bool':
        $val = (bool)$val;
        break;
    case 'float':
        $val = (float)$val;
        break;
    case 'array':
        // Cast value as an array only if it isn't already one
        if (!is_array($val)) {
            $val = array($val);
        }
        break;
    case 'string':
    default:
        $val = (string)$val;
        break;
    }
    return $val;
}


/**
 * Service function to store a message for later display as a popup.
 *
 * @param   array   $args       Arguments defining the message
 * @param   mixed   $output     Function output (not used)
 * @param   mixed   $svc_msg    Service message (not used)
 * @return  integer     Service return code
 */
function service_storeMessage_lglib($args, &$output='', &$svc_msg='')
{
    LGLIB_storeMessage($args);
    return PLG_RET_OK;
}


/**
 * Parse names using the NameParser class.
 *
 * @param   array   $args       Arguments: name and format
 * @param   array   $output     Name components returned in an assoc. array
 * @param   mixed   $svc_msg    Not used
 * @return  integer     Service return code
 */
function service_parseName_lglib($args, &$output, &$svc_msg)
{
    // Get the name string
    if (!isset($args['name'])) return array();
    // Get the function, if any
    $func = 'Parse';
    if (isset($args['format'])) {
        $valid_formats = array('L', 'F', 'LCF', 'LCFI', 'FL', 'FIL');
        if (in_array($args['format'], $valid_formats)) {
            $func = $args['format'];
        }
    } else {
        $func = 'Parse';
    }
    $output = LGLib\NameParser::$func($args['name']);
    return PLG_RET_OK;
}


/**
 * Get a name formatted according to the format string.
 *
 * @param   string  $name   Name to parse
 * @param   string  $format Name of a function in the NameParser class
 * @return  string|array    Parsed name string, or array of all components
 */
function plugin_parseName_lglib($name, $format = 'Parse')
{
    $valid_formats = array('L', 'F', 'LCF', 'LCFI', 'FL', 'FIL', 'Parse');
    if (in_array($format, $valid_formats)) {
        $func = $format;
    } else {
        $func = 'Parse';
    }
    return LGLib\NameParser::$func($name);
}


/**
 * Push a job onto the job queue.
 * Callable via PLG_callFunctionForOnePlugin()
 *
 * @param   string  $pi_name    Calling plugin name
 * @param   string  $job_name   Job name
 * @param   string  $param      Job parameters
 * @return  integer     Status from queue push function
 */
function plugin_pushjob_lglib($pi_name, $job_name, $params='')
{
    return LGLib\JobQueue::push($pi_name, $job_name, $params);
}


/**
 * Run internal scheduled tasks.
 * - purge completed jobs after an interval
 */
function plugin_runScheduledTask_lglib()
{
    if (Config::get('queue_purge_completed') > -1) {
        LGLib\JobQueue::purgeCompleted((int)Config::get('queue_purge_completed'));
    }
}

