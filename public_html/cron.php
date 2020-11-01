<?php
/**
 * Allows scheduled tasks to be run outside of glFusion.
 *
 * This can be used two ways:
 * - from a cron job, like so: `php -q cron.php <optional_security_key>`
 * - via a url, say from a monitoring system:
 *      `http://yoursite.com/lglib/cron.php?key=<optional_security_key>`
 *
 * The use of a security key is optional but recommended to avoid abuse.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2010-2014 Lee Garner
 * @package    subscription
 * @version    0.0.5
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once(dirname(__FILE__) . '/../lib-common.php');

if (php_sapi_name() == 'cli') {
    $args = getopt('fp:k:');
} else {
    $args = $_GET;
}

// if $force == true, run jobs regardless of last_run time value
$force = isset($args['f']) ? true : false;
// if a plugin name is provided, just run its jobs
$pi = isset($args['p']) ? $args['p'] : NULL;

// Check that the correct key value is supplied with the url or command line.
if (!empty($_LGLIB_CONF['cron_key'])) {
    if (!isset($args['k']) || $args['k'] !== $_LGLIB_CONF['cron_key']) {
        if (php_sapi_name() != 'cli') {
            // add forbidden header if accessed via web
            header('HTTP 1.0 Forbidden');
        }
        echo "DENIED\n";
        exit(1);
    }
}

if (!isset($_VARS['last_scheduled_run'])) {
    $_VARS['last_scheduled_run'] = 0;
}
if ($force || $_LGLIB_CONF['cron_schedule_interval'] > 0) {
    if ($force || ($_VARS['last_scheduled_run'] + $_LGLIB_CONF['cron_schedule_interval']) <= time()) {
        DB_query("UPDATE {$_TABLES['vars']} SET value=UNIX_TIMESTAMP() WHERE name='last_scheduled_run'" );
        if ($pi !== NULL) {
            if ($_CONF['cron_schedule_interval'] == 0) {
                // Only call regular tasks if system cron interval is unset,
                // otherwise leave it to the system cron
                PLG_runScheduledTask();
            }
            LGLIB_backup_database();
        }
        LGLib\JobQueue::run($pi);
    }
}
echo "SUCCESS\n";

/**
 * Back up the database according to a scheduled interval.
 */
function LGLIB_backup_database()
{
    global $_VARS, $_TABLES;

    $interval = (int)$_VARS['db_backup_interval'];
    if ($interval > -1) {               // if cron backups not disabled
        $lastrun = (int)$_VARS['db_backup_lastrun'];
        if ($interval == 0 ||           // always run
            !$lastrun ||                // never run before
            $lastrun < (time() - $interval)) {  // time to run again
            USES_lglib_class_dbbackup();
            $backup = new dbBackup();
            $backup->cron_backup();
        }
    }
}

?>
