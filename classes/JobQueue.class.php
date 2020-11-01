<?php
/**
 * Manage the job queue.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 <lee@leegarner.com>
 * @package     lglib
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace LGLib;


/**
 * Class to handle the job queue.
 * @package lglib
 */
class JobQueue
{

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
    public static function push($pi_name, $job_name, $params='')
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
     * Run jobs in the job queue table.
     * Uses LGLIB_invokeService() to call the "runjob" service function for plugins.
     *
     * @param   string  $pi     Plugin name, empty for all plugins
     */
    public static function run($pi = '')
    {
        global $_TABLES, $_CONF;

        $sql = "SELECT * FROM {$_TABLES['lglib_jobqueue']} WHERE status = 'ready'";
        if (!empty($pi)) {
            $sql .= " AND pi_name = '" . DB_escapeString($pi) . "'";
        }
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            // Flag the job as running so it's not picked up by another invocation
            // of cron.php
            self::updateJobStatus($A['id'], 'running');
            $status = LGLIB_invokeService(
                $A['pi_name'],
                $A['jobname'],
                $A,
                $output,
                $svc_msg
            );
            // Set the final job running status
            self::updateJobStatus($A['id'], $status);
        }
    }


    /**
     * Update the status of jobs in the queue.
     *
     * @see     self::runJobs()
     * @param   integer $jobid      ID of job to update
     * @param   mixed   $status     Job completion status
     */
    public static function updateJobStatus($jobid, $status)
    {
        global $_TABLES, $_CONF;

        $sql = '';
        $jobid = (int)$jobid;
        switch ($status) {
        case PLG_RET_OK:
            $status = 'completed';
            $sql = ", completed = UNIX_TIMESTAMP()";
            break;
        case PLG_RET_ERROR:
            $status = 'plugin_error';
            break;
        case 'running':
            // verbatim status text supplied
            break;
        default:
            $status = 'unknown';
            break;
        }

        $jobid = (int)$jobid;
        DB_query(
            "UPDATE {$_TABLES['lglib_jobqueue']}
            SET status = '{$status}' $sql
            WHERE id = $jobid"
        );
    }

}

