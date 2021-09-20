<?php
/**
 * Upgrade routines for the lgLib plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2013-2019 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.9
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// Required to get the config values
global $_CONF, $_DB_dbms;
use LGLib\Config;

/** Include the default configuration values */
require_once __DIR__ . '/install_defaults.php';

/** Include the table creation strings */
require_once __DIR__ . "/sql/mysql_install.php";

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean     True on success, False on failure
 */
function LGLIB_do_upgrade($dvlp=false)
{
    global $_LGLIB_DEFAULTS, $_PLUGIN_INFO, $_CONF;

    if (isset($_PLUGIN_INFO[Config::PI_NAME])) {
        if (is_array($_PLUGIN_INFO[Config::PI_NAME)) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[Config::PI_NAME]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[Config::PI_NAME];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_lglib();

    // Get the config object
    $c = config::get_instance();

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        // upgrade from 0.0.1 to 0.0.2
        $current_ver = '0.0.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!LGLIB_do_upgrade_sql($current_ver)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.4')) {
        // upgrade from 0.0.3 to 0.0.4
        $current_ver = '0.0.4';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('img_disp_relpath', $_LGLIB_DEFAULTS['img_disp_relpath'],
                'text', 0, 0, 15, 20, true, Config::PI_NAME);
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.5')) {
        // upgrade from 0.0.4 to 0.0.5
        $current_ver = '0.0.4';
        COM_errorLog("Updating Plugin to $current_ver");
        $c = config::get_instance();
        $c->add('cron_schedule_interval', $_LGLIB_DEFAULTS['cron_schedule_interval'],
                'text', 0, 0, 15, 30, true, Config::PI_NAME);
        $c->add('cron_key', $_LGLIB_DEFAULTS['cron_key'],
                'text', 0, 0, 15, 40, true, Config::PI_NAME);
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.6')) {
        // upgrade from 0.0.5 to 0.0.6
        $current_ver = '0.0.6';
        COM_errorLog("Updating Plugin to $current_ver");
        $c = config::get_instance();
        $c->add('img_cache_interval', $_LGLIB_DEFAULTS['img_cache_interval'],
                'text', 0, 0, 15, 50, true, Config::PI_NAME);
        $c->add('img_cache_maxage', $_LGLIB_DEFAULTS['img_cache_maxage'],
                'text', 0, 0, 15, 60, true, Config::PI_NAME);
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.0.7')) {
        // upgrade from 0.0.6 to 0.0.7
        $current_ver = '0.0.7';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('slimbox_autoactivation', $_LGLIB_DEFAULTS['slimbox_autoactivation'],
                'select', 0, 0, 3, 70, true, Config::PI_NAME);
        if (!LGLIB_do_upgrade_sql($current_ver)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.1')) {
        // upgrade to 1.0.1
        $current_ver = '1.0.1';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('use_lglib_messages', $_LGLIB_DEFAULTS['use_lglib_messages'],
                'select', 0, 0, 3, 80, true, Config::PI_NAME);
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.5')) {
        // upgrade to 1.0.5
        $current_ver = '1.0.5';
        COM_errorLog("Updating Plugin to $current_ver");
        $c->add('enable_smartresizer', $_LGLIB_DEFAULTS['enable_smartresizer'],
                'select', 0, 0, 3, 90, true, Config::PI_NAME);

        // Make sure default cache directory is set up
        $datadir = $_CONF['path'] . 'data/' . Config::PI_NAME;
        $dirs = array($datadir,
            $datadir . '/0', $datadir . '/1', $datadir . '/2', $datadir . '/3',
            $datadir . '/4', $datadir . '/5', $datadir . '/6', $datadir . '/7',
            $datadir . '/8', $datadir . '/9', $datadir . '/a', $datadir . '/b',
            $datadir . '/c', $datadir . '/d', $datadir . '/e', $datadir . '/f',
            $datadir . '/cache',
        );
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
            } else {
                mkdir($dir, 0755);
            }
        }
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.6')) {
        // upgrade to 1.0.6
        $current_ver = '1.0.6';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!LGLIB_do_upgrade_sql($current_ver)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.8')) {
        // upgrade to 1.0.8
        $current_ver = '1.0.8';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!LGLIB_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.9')) {
        // upgrade to 1.0.9
        $current_ver = '1.0.9';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!LGLIB_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.0')) {
        // upgrade to 1.1.0
        $current_ver = '1.1.0';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!LGLIB_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!LGLIB_do_set_version($current_ver)) return false;
    }

    // Final version update to catch updates that don't go through
    // any of the update functions, e.g. code-only updates
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!LGLIB_do_set_version($installed_ver)) return false;
    }

    // Update the configuration items
    global $lglibConfigData;
    USES_lib_install();
    require_once __DIR__ . '/install_defaults.php';
    _update_config('lglib', $lglibConfigData);

    // Remove deprecated files
    LGLIB_remove_old_files();
    return true;
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean         True on success, False on failure
 */
function LGLIB_do_upgrade_sql($version, $dvlp=false)
{
    global $_TABLES, $_UPGRADE_SQL;

    // If no sql statements passed in, return success
    if (
        !isset($_UPGRADE_SQL[$version]) ||
        !is_array($_UPGRADE_SQL[$version])
    ) {
        return true;
    }

    // Execute SQL now to perform the upgrade
    COM_errorLog("--Updating lgLib to version $version");
    foreach ($_UPGRADE_SQL[$version] as $q) {
        COM_errorLog("lgLib Plugin $version update: Executing SQL => $q");
        DB_query($q, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during lgLib plugin update: $q",1);
            if (!$dvlp) {
                return false;
            }
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function LGLIB_do_set_version($ver)
{
    global $_TABLES;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '" . Config::get('pi_version') . "',
            pi_gl_version = '" . Config::get('gl_version' . "',
            pi_homepage = '" . Config::get('url'} . "'
        WHERE pi_name = '" . Config::PI_NAME . "'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the " . Config::get('pi_display_name'} . " Plugin version",1);
        return false;
    } else {
        return true;
    }
}


/**
 * Remove deprecated files.
 */
function LGLIB_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/lglib
        __DIR__ => array(
            // 1.0.7
            'classes/NameParser_tmp.class.php',
            // 1.0.14
            'language/english.php',
            'language/polish.php',
        ),
        // public_html/lglib
        $_CONF['path_html'] . 'lglib' => array(
            // 1.0.14
            'docs/english/config.legacy.html',
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
}
