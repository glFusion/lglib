<?php
/**
*   Table definitions for the lgLib plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012-2018 Lee Garner <lee@leegarner.com>
*   @package    lglib
*   @version    1.0.8
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** @global array $_TABLES */
global $_TABLES, $_SQL, $_UPGRADE_SQL;

$_SQL['lglib_messages'] = "CREATE TABLE {$_TABLES['lglib_messages']} (
  `uid` int(11) NOT NULL DEFAULT '1',
  `sess_id` varchar(80) NOT NULL DEFAULT '',
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `pi_code` varchar(40) DEFAULT NULL,
  `persist` tinyint(1) unsigned DEFAULT '0',
  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires` datetime DEFAULT NULL,
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1',
  KEY `uid` (`uid`),
  KEY `sess_id` (`sess_id`)
) ENGINE=MyISAM";

$_SQL['lglib_jobqueue'] = "CREATE TABLE `{$_TABLES['lglib_jobqueue']}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `submitted` int(11) unsigned,
  `completed` int(11) unsigned,
  `pi_name` varchar(20) DEFAULT NULL,
  `jobname` varchar(40) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ready',
  `params` text,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM";

$_UPGRADE_SQL = array(
'0.0.2' => array(
    "CREATE TABLE {$_TABLES['lglib_messages']} (
      `uid` int(11) NOT NULL DEFAULT '1',
      `sess_id` varchar(255) NOT NULL DEFAULT '',
      `title` varchar(255) DEFAULT NULL,
      `message` text NOT NULL,
      `pi_code` varchar(255) DEFAULT NULL,
      `persist` tinyint(1) unsigned default 0,
      `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `expires` datetime
    ) ENGINE=MyISAM",
),
'0.0.7' => array(
  "CREATE TABLE IF NOT EXISTS `{$_TABLES['lglib_jobqueue']}` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `pi_name` varchar(20) DEFAULT NULL,
    `jobname` varchar(40) DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'ready',
    `params` text,
    PRIMARY KEY (`id`)
    ) ENGINE=MyISAM",
),
'1.0.6' => array(
    "ALTER TABLE {$_TABLES['lglib_messages']}
        ADD `level` tinyint(1) unsigned NOT NULL DEFAULT '1',
        ADD KEY `uid` (`uid`),
        ADD KEY `sess_id` (`sess_id`)",
    ),
'1.0.8' => array(
    "ALTER TABLE {$_TABLES['lglib_messages']} CHANGE sess_id sess_id varchar(80) not null",
    "ALTER TABLE {$_TABLES['lglib_messages']} CHANGE pi_code pi_code varchar(40)",
    ),
'1.0.9' => array(
    "TRUNCATE {$_TABLES['lglib_jobqueue']}",
    "ALTER TABLE {$_TABLES['lglib_jobqueue']} CHANGE submitted submitted int(11) unsigned",
    "ALTER TABLE {$_TABLES['lglib_jobqueue']} ADD completed int(11) unsigned AFTER submitted",
    "ALTER TABLE {$_TABLES['lglib_jobqueue']} ADD key `idx_status` (status)",
),
);

?>
