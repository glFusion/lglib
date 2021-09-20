<?php
/**
 * Table names and other global configuration values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2021 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */
use LGLib\Config;

// Static configuration items
Config::set('pi_version', '1.1.0');
Config::set('gl_version', '1.7.9');
Config::set('pi_url', 'https://www.leegarner.com');

global $_TABLES, $_DB_table_prefix;
$_DB_prefix = $_DB_table_prefix . 'lglib_';
$_TABLES['lglib_messages'] = $_DB_prefix . 'messages';
$_TABLES['lglib_jobqueue'] = $_DB_prefix . 'jobqueue';
