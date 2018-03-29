<?php
/**
*   glFusion API functions for the lgLib plugin.
*   This plugin's job is simply to load javascript and css files that are
*   required by other plugins.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012-2018 Lee Garner <lee@leegarner.com>
*   @package    lglib
*   @version    1.0.6
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

/** Import configuration-handling class */
require_once $_CONF['path_system'] . 'classes/config.class.php';
$lgu_config = config::get_instance();
$_LGLIB_CONF = $lgu_config->get_config('lglib');

/** Additional config items and table definitions are added here */
require_once __DIR__ . '/lglib.php';
define('LGLIB_PI_PATH', __DIR__);
define('LGLIB_URL', $_CONF['site_url'] . '/' . $_LGLIB_CONF['pi_name']);
define('LGLIB_ADMIN_URL', $_CONF['site_admin_url'] . '/plugins/' . $_LGLIB_CONF['pi_name']);

// Define the global flags here, before any functions are called.
// This will force any older plugins that don't require this plugin to NOT load
// their versions of the CSS and JS.
define('HAVE_CALENDAR_CSS', $_LGLIB_CONF['pi_name']);
define('HAVE_SLIMBOX_CSS', $_LGLIB_CONF['pi_name']);
define('HAVE_CALENDAR_JS', $_LGLIB_CONF['pi_name']);
define('HAVE_SLIMBOX_JS', $_LGLIB_CONF['pi_name']);

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

// Session variables are used to pass path info to the TimThumb image resizer.
// Since img.php doesn't load lib-common.php (it would take forever to load
// images), these values have to get to it somehow.
// Yes, these can also be overridden via config.php
// The calling plugin needs to set $_SESSION['<pi_name>_origpath']
SESS_setVar('lglib', array(
    'origpath'      => $_CONF['path_html'] . 'images',
    'cache_dir'     => $_CONF['path'] . 'data/' . $_LGLIB_CONF['pi_name'],
    'image_lib'     => $_CONF['image_lib'],
    'path_to_mogrify' => $_CONF['path_to_mogrify'],
));

// Set default message location to top center unless otherwise set
if (!isset($_SYSTEM['alert_position'])) {
    $_SYSTEM['alert_position'] = 'top-center';
}

$_LGLIB_CONF['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? true : false;

/**
*   Include the plugin-specific language file, and the corresponding
*   admin-created custom language file, if any.  Looks for the site
*   language first, then defaults to english.php if nothing else found.
*/
$langpath = __DIR__ . '/language';
if (is_file("$langpath/{$_CONF['language']}.php")) {
    $langfile = "{$_CONF['language']}.php";
} else {
    $langfile = 'english.php';
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

spl_autoload_register(function($class)
{
    if (strpos($class, 'LGLib\\') === 0) {
        $class = str_replace('LGLib\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
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


/** Include the PEAR Date Calc class */
function USES_lglib_class_datecalc()
{
    require_once __DIR__ . '/classes/DateCalc.class.php';
}

function USES_lglib_class_nameparser()
{
    // Include a shim class during the transition to namespaces.
    require_once __DIR__ . '/classes/NameParser_tmp.class.php';
    //require_once __DIR__ . '/classes/HumanNameParser.class.php';
}

function USES_lglib_class_image()
{
    require_once __DIR__ . '/classes/image.class.php';
}

/** Database backup class */
function USES_lglib_class_dbbackup()
{   require_once __DIR__ . '/classes/dbBackup.class.php';    }

/** PDF generation
*   If a derived class file is requested, e.g "PDF_HTML", then load that file.
*   otherwise, load the base FPDF class file.
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

function USES_lglib_class_tcpdf()
{
    require_once __DIR__ . '/classes/pdf/html2pdf/vendor/tecnickcom/tcpdf/tcpdf.php';
}

function USES_lglib_class_html2pdf()
{   require_once __DIR__ . '/classes/pdf/html2pdf/vendor/autoload.php'; }

/**
*   Get the currently installed version.
*
*   @return string  version number
*/
function plugin_chkVersion_lglib()
{
    global $_LGLIB_CONF;
    return $_LGLIB_CONF['pi_version'];
}


/**
*   Puts an option for the plugin in the command and control block.
*   Not used in glFusion versions > 1.6.0 since the database backup function
*   has been updated in the main DB Backup option.
*
*   @return   array  Array of (plugin name, admin url, url of plugin icon)
*/
function plugin_cclabel_lglib()
{
    global $LANG_LGLIB, $_CONF;

    if (SEC_inGroup('Root') && GVERSION < '1.6.1' && $_CONF['allow_mysql_dump']) {
        return array(
            $LANG_LGLIB['menu_label'],
            LGLIB_ADMIN_URL . '/index.php',
            plugin_geticon_lglib(),
        );
    } else {
        return '';
    }
}


/**
*   When this plugin is disabled, notify other plugins.
*/
function plugin_enablestatechange_lglib($enable)
{
    if (!$enable) {
COM_errorLog("calling itemdeleted");
        PLG_itemDeleted('*', 'lglib');
    }
}


/**
*   Adds the plugin to the Admin menu.
*
*   @return array   Array(pi_name, admin url, # of items) or '' for none.
*/
function plugin_getadminoption_lglib()
{
    global $_CONF, $LANG_LGLIB;

    if (SEC_inGroup('Root') && GVERSION < '1.6.1' && $_CONF['allow_mysql_dump']) {
        $retval = array(
            $LANG_LGLIB['menu_label'],
            LGLIB_ADMIN_URL . '/index.php',
            0,
        );
    } else {
        $retval = '';
    }
    return $retval;
}


/**
*   Returns the URL of the plugin's icon.
*
*   @return string      URL of the icon
*/
function plugin_geticon_lglib()
{
    return LGLIB_URL . '/images/lglib.png';
}


/**
*   Get additional CSS into the site header.
*   Allows for custom CSS to be placed by the site admin.
*
*   @return array   List of paths to CSS files
*/
function plugin_getheadercss_lglib()
{
    global $_CONF, $_LGLIB_CONF, $_SYSTEM;

    // glFusion 1.4 uses different paths to css cache than 1.5.0, so need
    // different versions of css files.
    $prefix = GVERSION < '1.5.0' ? '140/' : '150/';

    $styles = array();

    $files = array(
        'style.css',
        // Include the popup calendar CSS.
        "{$prefix}calendar-{$_LGLIB_CONF['cal_style']}.css",
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
    $layout_css = "{$_CONF['path_layout']}plugins/{$_LGLIB_CONF['pi_name']}/";
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
*   Gets the list of javascript files required for this plugin.
*
*   @return array       List of javascript files to load
*/
function plugin_getheaderjs_lglib()
{
    global $_CONF, $_SYSTEM, $_LGLIB_CONF;

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

        // modal popups
        //"$js_dir/popup.js",

        // ajax library
        //"$js_dir/ajax.js",
    );

    // Either jQuery or Mootools
    if (!isset($_SYSTEM['disable_mootools']) || $_SYSTEM['disable_mootools'] == false) {
        $files[] = "$js_dir/slimbox.js";
    } /*else {
        $files[] = "$js_dir/lglib_misc.js";
    }*/

    if (!isset($_SYSTEM['disable_jquery_slimbox']) || $_SYSTEM['disable_jquery_slimbox'] == false) {
        if (GVERSION < '1.6.0') {
            $files[] = "$js_dir/slimbox2.js";
        }
        if ($_LGLIB_CONF['slimbox_autoactivation']) {
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
*   Get the link to a documentation file.
*   Returns the URL to the file, or to the directory if no file requested.
*
*   @see    plugin_getconfigelementhelp_subscription()
*   @param  string  $file       HTML file
*   @param  string  $pi_name    Name of plugin, default "lglib"
*   @param  string  $doclang    Documentation language, default English
*   @return string              URL to document directory or file
*/
function LGLIB_getDocURL($file='', $pi_name='lglib', $doclang='english')
{
    global $_CONF, $_LGLIB_CONF;

    $doc_path = "{$_CONF['path_html']}$pi_name/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        // Split out any .html extension to insert ".legacy" if needed
        $fparts = pathinfo($file);
        $file = $fparts['filename'];
        if (version_compare(GVERSION, '1.6.0', '<')) {
            $file .= '.legacy';
        }
        $file .= '.html';

        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/";
    }
    return $doc_url;
}


/**
*   Get the URL to the configuration help file.
*
*   @param  string  $option     Config option selected
*   @param  string  $doclang    Document language
*   @return string              URL to document
*/
function plugin_getconfigelementhelp_lglib($option, $doclang='english')
{
    global $_LGLIB_CONF;

    $doc_url = LGLIB_getDocURL('config.html', $_LGLIB_CONF['pi_name'], $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }

    return array($doc_url, 0);
}


/**
*   Upgrade the plugin to the currently-installed version.
*
*   @return boolean True if successful, False otherwise
*/
function plugin_upgrade_lglib()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return LGLIB_do_upgrade();
}


/**
*   Set template variables.
*   Puts any messages to be displayed into the lglib_messags
*   template variable, if used, or appends them to the info_block
*   variable that should be part of every header.thtml template.
*
*   @param  string  $templatename   Name of template, e.g. 'header'
*   @param  ref     $template       Reference to actual template
*/
function plugin_templateSetVars_lglib($templatename, &$template)
{
    global $_LGLIB_CONF;

    static $messages = NULL;

    // Get messages to display to the user and put them in the template
    // This can be called more than once per page display, so load
    // the messages only once.
    if ($messages === NULL) {
        $messages = LGLIB_showAllMessages();
    }
    if ($messages != '') {
        if ($_LGLIB_CONF['use_lglib_messages']) {
            $template->set_var('lglib_messages', $messages);
        } elseif ($templatename == 'footer') {
            // Have to do this during the 'footer' template to make sure it's
            // only done once.
            $info_block = $template->get_var('info_block') . $messages;
            $template->set_var('info_block', $info_block);
        }
    }

    // Resize images referenced in the templates if so configured
    // For glFusion 1.7.0 and up see plugin_outputfilter_lglib
    if (GVERSION < '1.7.0' && $_LGLIB_CONF['enable_smartresizer']) {
       require_once __DIR__ . '/classes/SmartResizer.class.php';
       if ($templatename == 'article') {
            LGLib\SmartResizer::Template($template, 'story_introtext');
            LGLib\SmartResizer::Template($template, 'story_introtext_only');
            LGLib\SmartResizer::Template($template, 'story_bodytext_only');
            LGLib\SmartResizer::Template($template, 'story_text_no_br');
        }
    }
}


/**
*   Perform filtering and updating on final output before display
*
*   @param  string  $output     HTML output to process
*   @param  string  $plugin     Invoking plugin
*   @return string              Updated $output
*/
function plugin_outputfilter_lglib($output, $plugin='')
{
    global $_LGLIB_CONF;

    // Resize images referenced in the templates if so configured
    if ($_LGLIB_CONF['enable_smartresizer']) {
       //require_once __DIR__ . '/classes/SmartResizer.class.php';
       LGLib\SmartResizer::Text($output);
    }
    return $output;
}


/**
*   Invoke a service in another plugin.
*   This is our own version of PLG_invokeService() so the other plugins
*   don't have to enable web services.  This is not used by this plugin,
*   but is available for other plugins.
*
*   Service function names are formatted as "service_SERVICENAME_PLUGIN()"
*
*   @param  string  $type       Plugin name (PLUGIN)
*   @param  string  $action     Plugin service (SERVICENAME)
*   @param  array   $args       Array of arguments to pass to the plugin
*   @param  mixed   &$output    Pointer to output values
*   @param  mixed   &$svc_msg   Pointer to service message
*   @return integer Return value, see lib-plugins.php
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
*   Store a message in the database that can be retrieved later by
*   another plugin. This provides a more flexible method for showing popup
*   messages than the numbered-message method.
*
*   @param  mixed   $args   Message to be displayed, or argument array
*   @param  string  $title  Optional title
*   @param  boolean $persist    True if the message should persist onscreen
*   @param  string  $expire SQL-formatted expiration datetime
*   @param  string  $pi_code    Name of plugin storing the message
*   @param  integer $uid        ID of the user to view the message
*   @param  boolean $use_sess_id    True to use session ID to retrieve
*/
function LGLIB_storeMessage($args, $title='', $persist=false, $expires = '',
    $pi_code = '', $uid = 0, $use_sess_id = true)
{
    global $LANG_LGLIB, $_CONF, $_LGLIB_CONF, $_USER;

    // Convert to a single "args" array argument, but maintain backward
    // compatibility for some time.
    $level = 'info';
    if (is_array($args)) {
        if (isset($args['message']))
            $msg = $args['message'];
        elseif (isset($args['msg']))
            $msg = $args['msg'];
        if (isset($args['title']) && empty($title))
            $title = $args['title'];
        if (isset($args['persist']))
            $persist = $args['persist'] == true ? true : false;
        if (isset($args['expires']) && empty($expires))
            $expires = $args['expires'];
        if (isset($args['pi_code']) && empty($pi_code))
            $pi_code = $args['pi_code'];
        elseif (isset($args['plugin']))
            $pi_code = $args['plugin'];
        if (isset($args['uid']) && empty($uid))
            $uid = (int)$args['uid'];
        if (isset($args['use_sess_id']))
            $use_sess_id = $args['use_sess_id'] == true ? true : false;
        if (isset($args['level']))
            $level = $args['level'];
    } else {
        $msg = $args;
    }
    if (empty($msg)) return;
    if ($title == '') $title = $LANG_LGLIB['system_message'];
    if ($persist != false) $persist = true;
    switch ($level) {
    case 'error':
    case 'err':
    case false:
    case 3:
        $level = 3; break;
    case 'warn':
    case 'warning':
    case 2:
        $level = 2;
        break;
    case 'info':
    case true;
    case 1:
    default:
        $level = 1;
        break;
    }
    //if ($_LGLIB_CONF['msg_store'] == 'db') {
    if (LGLIB_MSG_STORE == 'db') {
        global $_TABLES;

        $uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        $sess_id = $use_sess_id ? session_id() : '';
        $persist = $persist ? 1 : 0;
        if (empty($expires)) {
            if ($uid < 2) {
                // Anonymous messages expire in 4 hours
                $expires = 'DATE_ADD(NOW(), INTERVAL 4 HOUR)';
            } else {
                // Member messages exist until viewed
                $expires = "'2037-12-31'";  // non-expiring
            }
        } else {
            $expires = "'".DB_escapeString($expires)."'";
        }
        $msg = DB_escapeString($msg);
        if ($title != '') $title = DB_escapeString($title);
        if ($pi_code != '') $pi_code = DB_escapeString($pi_code);
        $sql = "INSERT INTO {$_TABLES['lglib_messages']}
            (uid, sess_id, title, message, persist, expires, pi_code, level)
            VALUES ('$uid', '$sess_id', '$title', '$msg', $persist,
                $expires, '$pi_code', $level)";
        DB_query($sql, 1);
        if (DB_error()) COM_errorLog("lglib: storeMessage SQL error: $sql");
    } else {
        // Session-based messages can't be saved for another user.
        if ($uid > 0 && $uid != $_USER['uid']) return;
        // Make sure the session variable is available.
        if (!isset($_SESSION[LGLIB_MSG_VAR]))
            $_SESSION[LGLIB_MSG_VAR] = array();
        $_SESSION[LGLIB_MSG_VAR][] = array(
            'title' => $title, 'message' => $msg, 'persist' => $persist
        );
    }
}


/**
*   Retrieve all messages for display.
*   Gets all messages from the DB where the user ID matches for
*   non-anonymous users, OR the session ID matches. This allows a message
*   caused by an anonymous action to be displayed to the user after login.
*
*   @return array   Array of messages, title=>message
*/
function LGLIB_getAllMessages()
{
    global $_TABLES, $_CONF, $_LGLIB_CONF, $_USER;

    $messages = array();

    if (LGLIB_MSG_STORE == 'db') {
        $uid = (int)$_USER['uid'];
        $q = array();
        if ($uid > 1) $q[] = "uid = $uid";
        // Get the session ID for messages to anon users. If a message was
        // stored before the user was logged in this will allow them to see it.
        $sess_id = DB_escapeString(session_id());
        if (!empty($sess_id)) $q[] = "sess_id = '$sess_id'";
        if (empty($q)) return $messages;
        $query = implode(' OR ', $q);
        $sql = "SELECT title, message, persist, level
                FROM {$_TABLES['lglib_messages']}
                WHERE $query
                ORDER BY dt DESC";
        $result = DB_query($sql, 1);
        if ($result) {
            while ($A = DB_fetchArray($result, false)) {
                $messages[] = array(
                    'title'     => $A['title'],
                    'message'   => $A['message'],
                    'persist'   => $A['persist'] ? true : false,
                    'level'     => $A['level'],
                );
            }
        }
    } else {
        $messages = SESS_getVar(LGLIB_MSG_VAR);
        if ($messages == 0) $messages = array();
    }
    return $messages;
}


/**
*   Delete all messages for a user
*/
function LGLIB_deleteAllMessages()
{
    if (LGLIB_MSG_STORE == 'session') {
        SESS_setVar(LGLIB_MSG_VAR, array());
    } else {
        global $_TABLES, $_USER;
        $uid = (int)$_USER['uid'];
        $q = array();
        if ($uid > 1) $q[] = "uid = $uid";
        $sess_id = DB_escapeString(session_id());
        if (!empty($sess_id)) $q[] = "sess_id = '$sess_id'";
        if (empty($q)) return;
        $query = implode(' OR ', $q);
        $sql = "DELETE FROM {$_TABLES['lglib_messages']} WHERE $query";
        $result = DB_query($sql);
         DB_delete($_TABLES['lglib_messages'], 'uid', $_USER['uid']);
    }
}

/**
*   Delete a single message
*   Called by plugins to remove a message placed earlier. At least one of
*   $uid or $pi_code must be present
*
*   @param  integer $uid    User ID, required, can be zero to ignore
*   @param  string  $pi_code    Optional plugin code value.
*/
function LGLIB_deleteMessage($uid, $pi_code = '')
{
    global $_TABLES;

    $fields = array();
    $values = array();
    if ($uid > 0) {
        $fields[] = 'uid';
        $values[] = $uid;
    }
    if ($pi_code != '') {
        $fields[] = 'pi_code';
        $values[] = $pi_code;
    }
    if (empty($fields)) return; // this function only deletes specific messages
    DB_delete($_TABLES['lglib_messages'], $fields, $values);
}


/**
*   Delete expired messages
*   Only applies when using DB as the message store
*/
function LGLIB_expireMessages()
{
    if (LGLIB_MSG_STORE == 'db') {
        global $_TABLES;
        $sql = "DELETE FROM {$_TABLES['lglib_messages']}
                WHERE expires < NOW()";
        DB_query($sql, 1);
    }
}


/**
*   Display all messagse for a user's session.
*   If $persist is true, or any $msg['persist'] == true, then the displayed
*   message will stay on the screen. Otherwise the message fades out.
*
*   @param  boolean $persist    Keep the message box open? False = fade out
*   @return string      HTML for message box
*/
function LGLIB_showAllMessages($persist = false)
{
    $retval = '';

    LGLIB_expireMessages();

    $msgs = LGLIB_getAllMessages();
    // Include a zero element if level is undefined
    $levels = array('info', 'info', 'warning', 'error');

    if (empty($msgs)) return '';
    if (count($msgs) == 1) {
        $message = $msgs[0]['message'];
        $title = $msgs[0]['title'];
        $level = $msgs[0]['level'];
        if ($msgs[0]['persist']) $persist = true;
    } else {
        $message = '';
        $title = '';
        $level = 1;     // Default to "info"
        foreach ($msgs as $msg) {
            $message .= '<li class="lglmessage">' .
                    $msg['message'] .
                    '</li>'.LB;
            // If any message requests "persist", then all persist
            if ($msg['persist']) $persist = true;
            // Set to the highest (worst) error level
            if ($msg['level'] > $level) $level = $msg['level'];
            // First title found in a message gets used instead of default
            if (empty($title) && !empty($msg['title'])) $title = $msg['title'];
        }
        $message = '<ul class="lglmessage">' . $message . '</ul>' . LB;
    }
    LGLIB_deleteAllMessages();
    // Revert to the system message title if no other title found
    if (empty($title)) $title = $LANG_LGLIB['system_message'];
    $leveltxt = isset($levels[$level]) ? $levels[$level] : 'info';
    if ($persist) {
        return '<span class="' . $leveltxt . ' lglmessage">' . $message . '</span>' . LB;
    } else {
        return COM_showMessageText($message, $title, $persist, $leveltxt);
    }
}


/**
*   Strips slashes if magic_quotes_gpc is on.
*
*   @param  mixed   $var    Value or array of values to strip.
*   @return mixed           Stripped value or array of values.
*/
function LGLIB_stripslashes($var)
{
	if (get_magic_quotes_gpc()) {
		if (is_array($var)) {
			return array_map('LGLIB_stripslashes', $var);
		} else {
			return stripslashes($var);
		}
	} else {
		return $var;
	}
}


function LGLIB_loginPopup()
{
    return '<div id="modal" style="border:3px solid black; background-color:#9999ff; padding:25px; font-size:150%; text-align:center; display:none;">' .
        LGLIB_loginform() .
        '</div>';
}


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
*   A generic function to store pseudo-global variables
*
*   @param  string  $name   Variable name
*   @param  mixed   $value  Value to store
*   @param  boolean $clobber    False to avoid overwriting
*/
function LGLIB_setGlobal($name, $value, $clobber=true)
{
    global $_LGLIB_GLOBALS;
    if ($clobber || !isset($_LGLIB_GLOBALS[$name])) {
        $_LGLIB_GLOBALS[$name] = $value;
    }
}


/**
*   Retrieve a pseudo-global variable.
*
*   @param  string  $type   Name of variable
*   @param  boolean $remove True to unset the variable, False to leave it
*   @return mixec           Session variable contents
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
*   A generic function to store session variables
*
*   @deprecated
*   @see    SESS_setVar()
*   @param  string  $name   Session variable name
*   @param  string  $value  Value to store
*   @param  boolean $clobber    False to avoid overwriting
*/
function LGLIB_setSession($name, $value, $clobber=true)
{
    if ($clobber || !isset($_SESSION[$name])) {
        $_SESSION[$name] = $value;
    }
}


/**
*   Retrieve a session variable.
*
*   @deprecated
*   @see    SESS_getVar()
*   @param  string  $name   Type or name of variable
*   @param  boolean $remove True to unset the variable, False to leave it
*   @return mixec           Session variable contents
*/
function LGLIB_getSession($name, $remove=false)
{
    if (isset($_SESSION[$name])) {
        $retval = $_SESSION[$name];
        if ($remove) unset($_SESSION[$name]);
    } else {
        $retval = NULL;
    }
    return $retval;
}


/**
*   Log activity to a plugin-specific log file
*   Logs will be written to /private/logs/<pi_name>.log
*
*   @param  string  $pi_name    Plugin name
*   @param  string  $logentry   Text to log
*/
function LGLIB_auditLog($pi_name, $logentry = '')
{
    global $_CONF, $_USER, $LANG01;

    if ($logentry == '')
        return '';

    // A little sanitizing
    $logentry = str_replace(
            array('<?', '?>'),
            array('(@', '@)'),
            $logentry);

    $timestamp = strftime( '%c' );
    $logfile = $_CONF['path_log'] . $pi_name . '.log';

    // Can't open the log file?  Return an error
    if (!$file = fopen($logfile, 'a')) {
        COM_errorLog("Cannot open {$pi_name}.log. Msg: $logentry");
        return;
    }

    // Get the user name if it's not anonymous
    if (isset($_USER['uid'])) {
        $byuser = $_USER['uid'] . '-'.
                COM_getDisplayName($_USER['uid'],
                    $_USER['username'], $_USER['fullname']);
    } else {
        $byuser = 'anon';
    }
    $byuser .= '@' . $_SERVER['REMOTE_ADDR'];

    // Write the log entry to the file
    // Can't open the log file?  Fall back to COM_errorLog
    if ($file = fopen($logfile, 'a')) {
        fputs($file, "$timestamp ($byuser) - $logentry\n");
    } else {
        COM_errorLog("$timestamp ($plugin / $byuser) - $logentry\n");
    }
    fclose($file);
}


/**
*   Convert a string to title case, ignoring small words
*   e.g. "a test of a title function" becomes "A Test of a Title Function"
*
*   @param  string  $str    String to be converted
*   @return string          Converted title string
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
*   Get the URL to an image.
*   New version just calls service_imageurl_lglib() and returns the url
*   for backward compatibility.
*
*   Sample usage:
*       echo '<img src=" . LGLIB_ImageUrl(/some/file/path.jpg, 800, 600) . '">';
*
*   @param  string  $orig_path  Full path to the original image
*   @param  integer $width      Image width
*   @param  integer $height     Image height
*   @param  mixed   $rnd        Optional random ID to force image recreation
*   @return string              Full URL to the image, empty on error
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
*   Obfuscate a string (e.g. email address) so it can be displayed safely.
*   Borrowed from Dokuwiki's configuration.
*
*   @param  string  $str    String to obfuscate
*   @param  string  $p2     Remainder of string after first whitespace
*   @return string          Obfuscated email address.
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
*   Push a job onto the job queue
*   Called by plugins to add a job to the queue.
*   $pi_name and $job_name are required, $params may be a string or
*   an array. If it's an array it is converted to JSON before storage.
*
*   @param  string  $pi_name    Name of plugin to execute the job
*   @param  string  $job_name   Name of job to be run
*   @param  mixed   $params     String or Array of parameters
*   @return bookean     True on success, False on failure.
*/
function LGLIB_pushJob($pi_name, $job_name, $params='')
{
    if (is_array($params)) {
        $json_params = json_encode($params);
        if ($json_params === false) return false;
    } else {
        $json_params = $params;
    }

    $pi_name = DB_escapeString($pi_name);
    $job_name = DB_escapeString($job_name);
    $params = DB_escapeString($json_params);
    $sql = "INSERT INTO gl_lglib_jobqueue (pi_name, jobname, params)
            VALUES ('$pi_name', '$job_name', '$params')";
    DB_query($sql, false);
    if (DB_error()) {
        return false;
    } else {
        return true;
    }
}


/**
*   Run jobs in the job queue table
*   Uses LGLIB_invokeService() to call the "runjob" service function
*   for plugins.
*
*   @param  string  $pi     Plugin name, empty for all plugins
*/
function LGLIB_run_jobqueue($pi = '')
{
    global $_TABLES, $_CONF;

    $sql = "SELECT * FROM {$_TABLES['lglib_jobqueue']} WHERE status = 'ready'";
    if (!empty($pi)) $sql .= " AND pi_name = '" . DB_escapeString($pi) . "'";
    $res = DB_query($sql);
    while ($A = DB_fetchArray($res, false)) {
        $status = LGLIB_invokeService($A['pi_name'], 'runjob',
                $A, $output, $svc_msg);
        LGLIB_update_job_status($A['id'], $status);
    }
}


/**
*   Update the status of jobs in the queue.
*
*   @see    LGLIB_run_jobqueue()
*   @param  integer $jobid      ID of job to update
*   @param  integer $status     Job completion status
*/
function LGLIB_update_job_status($jobid, $status)
{
    global $_TABLES;

    static $statuses = array(
        PLG_RET_OK => 'completed',
        PLG_RET_ERROR => 'plugin error',
    );
    if (!isset($statuses[$status]))
        $statuses[$status] = 'undefined';

    $jobid = (int)$jobid;
    $status = (int)$statuses[$status];
    DB_query("UPDATE {$_TABLES['lglib_jobqueue']}
            SET status = '{$status}'
            WHERE id = $jobid");
}


/**
*   Get the image info for a resized image.
*
*   Args array is expected to ontain values for 'filepath', 'width', 'height',
*   and an optional random number for force image regeneration.
*
*   @uses   lgImage::ReSize()
*   @param  array   $args       Array of item information
*   @param  array   &$output    Pointer to output array
*   @param  array   &$svc_msg   Unused
*   @return integer             Status code
*/
function service_imageurl_lglib($args, &$output, &$svc_msg)
{
    global $_LGLIB_CONF, $_CONF;

    // Initialize return value to empty URL.
    // Maybe change to a stock "missing" image in the future.
    $output = array();

    // Sanitize arguments
    $width = isset($args['width']) ? (int)$args['width'] : 0;
    $height = isset($args['height']) ? (int)$args['height'] : 0;
    $rnd = isset($args['rnd']) ? (int)$args['rnd'] : 0;
    $orig_path = isset($args['filepath']) ? $args['filepath'] : '';

    // If original image doesn't exist, return empty url,
    // don't bother continuing.
    if (empty($orig_path) || !is_file($orig_path))
        return PLG_RET_ERROR;

    // Separate the path and create a MD5 hash of its path and size
    $path_parts = pathinfo($orig_path);
    $disp_fname = md5("$rnd-$orig_path-$width-$height") . '.' .
            $path_parts['extension'];

    // Image path portion of url
    $img_path = $_LGLIB_CONF['pi_name'] . '/' . $_LGLIB_CONF['img_disp_relpath'] .
            '/' . $disp_fname[0];

    // Image url relative to display base url
    $disp_relurl = $img_path . '/' . $disp_fname;

    // Display image full path, for checking file_exists and storing resized
    $disp_fullpath = $_CONF['path_html'] . $disp_relurl;

    $maxage = (int)$_LGLIB_CONF['img_cache_maxage'];
    $interval = (int)$_LGLIB_CONF['img_cache_interval'];
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
    if (!is_file($disp_fullpath)) {
        // Found an error with getting exif into in IMG_resizeImage(),
        // use less-robust version in lgImage instead
        USES_lglib_class_image();
        $A = lgImage::ReSize($orig_path, $disp_fullpath, $width, $height);
        $width = $A['d_width'];
        $height = $A['d_height'];
        $mime_type = $A['mime'];
    } else {
        // Have an existing file, need to get the dimensions
        $A = getimagesize($disp_fullpath);
        $width = $A[0];
        $height = $A[1];
        $mime_type = $A['mime'];
        @touch($disp_fullpath);
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
        );
        return PLG_RET_ERROR;
    }
}


/**
*   Get an icon from the CSS icon set, either UIkit or FontAwesome
*
*   @param  string  $name   Base name of icon, e.g. "edit" for "uk-icon-edit"
*   @param  string  $style  Style name from local CSS
*   @return string      Class names for icon
*/
function LGLIB_getIcon($name, $style='info')
{
    global $_LGLIB_CONF;

    if ($style != '')
        $style = ' lglib-icon-' . $style;

    if ($_LGLIB_CONF['_is_uikit']) {
        return ' uk-icon-' . $name . $style;
    } else {
        return 'fa fa-' . $name . $style;
    }
}


/**
*   Get the colorpicker JS and CSS.
*   There are default values for each parameter.
*   Sample parameters: array(
*       'fg_id'     => forground input field ID
*       'fg_color'  => current foreground color, including '#'
*       'bg_id'     => background input field ID
*       'bg_color'  => current background color, including '#'
*       'sample_id' => ID of sample display field
*
*   @param  array   $A  Optional array of field attributes
*   @return string      HTML to load JS and CSS for the colorpicker
*/
function LGLIB_colorPicker($A = array())
{
    static $need_js = true;
    if (!is_array($A)) return '';

    $T = new \Template(__DIR__ . '/templates');
    $T->set_file('picker', 'colorpicker.thtml');
    $T->set_block('fld', 'pickerJS', 'JS');
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

?>
