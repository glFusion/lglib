<?php
/**
 * Provides automatic installation of the lgLib plugin.
 * There is nothing to do except create the plugin record
 * since there are no tables or user interfaces.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2023 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     1.1.1
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @global string $_DB_dbms */
global $_DB_dbms, $_TABLES, $_SQL;

require_once __DIR__ . '/functions.inc';
require_once __DIR__ . '/lglib.php';
require_once __DIR__ . "/sql/{$_DB_dbms}_install.php";
use LGLib\Config;

//  Plugin installation options
$INSTALL_plugin[Config::PI_NAME] = array(
    'installer' => array(
        'type'      => 'installer',
        'version'   => '1',
        'mode'      => 'install',
    ),
    'plugin' => array(
        'type'      => 'plugin',
        'name'      => Config::PI_NAME,
        'ver'       => Config::get('pi_version'),
        'gl_ver'    => Config::get('gl_version'),
        'url'       => Config::get('pi_url'),
        'display'   => Config::get('pi_display_name'),
    ),

    array(
        'type'      => 'table',
        'table'     => $_TABLES['lglib_messages'],
        'sql'       => $_SQL['lglib_messages'],
    ),

    array(
        'type'      => 'table',
        'table'     => $_TABLES['lglib_jobqueue'], 
        'sql'       => $_SQL['lglib_jobqueue'],
    ),
);
    
 
/**
 * Puts the datastructures for this plugin into the glFusion database.
 * Note: Corresponding uninstall routine is in functions.inc.
 *
 * @return  boolean     True if successful False otherwise
 */
function plugin_install_lglib()
{
    global $INSTALL_plugin;

    COM_errorLog("Attempting to install the " . Config::PI_NAME . " plugin", 1);
    $ret = INSTALLER_install($INSTALL_plugin[Config::PI_NAME]);
    if ($ret > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Automatic removal function.
 *
 * @return  array       Array of items to be removed.
 */
function plugin_autouninstall_lglib()
{
    $out = array (
        'tables'    => array('lglib_messages', 'lglib_jobqueue'),
        'groups'    => array(),
        'features'  => array(),
        'php_blocks' => array(),
        'vars'      => array(
            Config::PI_NAME . '_dbback_exclude',
            Config::PI_NAME . '_dbback_sendto',
            Config::PI_NAME . '_dbback_files',
            Config::PI_NAME . '_dbback_cron',
            Config::PI_NAME . '_dbback_gzip',
            Config::PI_NAME . '_dbback_lastrun',
        ),
    );
    PLG_itemDeleted('*', Config::PI_NAME);
    return $out;
}


/**
 * Loads the configuration records for the Online Config Manager.
 *
 * @return  boolean     True = proceed, False = an error occured
 */
function plugin_load_configuration_lglib()
{
    require_once dirname(__FILE__) . '/install_defaults.php';
    return plugin_initconfig_lglib();
}


/**
 * Post-installation tasks.
 * 1. Create cache dirs. Fixes permissions if dire exists.
 */
function LGLIB_createPaths()
{
    global $_CONF;

    // Make sure default cache directory is set up
    $datadir = Config::get('path_imgcache');
    $dirs = array($datadir,
        $datadir . '/0', $datadir . '/1', $datadir . '/2', $datadir . '/3',
        $datadir . '/4', $datadir . '/5', $datadir . '/6', $datadir . '/7',
        $datadir . '/8', $datadir . '/9', $datadir . '/a', $datadir . '/b',
        $datadir . '/c', $datadir . '/d', $datadir . '/e', $datadir . '/f',
    );
    if (!is_dir($datadir)) {
        mkdir($datadir, 0755, true);
    }
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0755);
        } else {
            mkdir($dir, 0755);
            touch($dir . '/index.html');
        }
    }
}
