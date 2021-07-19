<?php
/**
 * Manage the job queue.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020-2021 <lee@leegarner.com>
 * @package     lglib
 * @version     v1.1.0
 * @since       v1.0.12
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
    const RUNNING = 99;

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
     * @return  integer     Number of jobs encountering an error
     */
    public static function run($pi = '')
    {
        global $_TABLES;

        $errors = 0;
        $sql = "SELECT * FROM {$_TABLES['lglib_jobqueue']} WHERE status = 'ready'";
        if (!empty($pi)) {
            $sql .= " AND pi_name = '" . DB_escapeString($pi) . "'";
        }
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $status = self::_runJob($A);
            if ($status != PLG_RET_OK) {
                $errors++;
            }
        }
        return $errors;
    }


    /**
     * Run a collection of jobs by job ID.
     *
     * @param   array|integer   One or more job IDs
     * @return  integer     Number of plugin errors encounterd
     */
    public static function runById($ids)
    {
        global $_TABLES;

        $errors = 0;
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $ids = implode(',', $ids);
        $sql = "SELECT * FROM {$_TABLES['lglib_jobqueue']} WHERE status = 'ready'
            AND id IN ($ids)";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $status = self::_runJob($A);
            if ($status != PLG_RET_OK) {
                $errors++;
            }
        }
        return $errors;
    }


    /**
     * Run a single job from the DB record.
     *
     * @param   array   $A      Single database record.
     * @return  integer     Plugin result code
     */
    private static function _runJob($A)
    {
        // Flag the job as running so it's not picked up by another invocation
        // of cron.php
        self::updateJobStatus($A['id'], self::RUNNING);
        $status = LGLIB_invokeService(
            $A['pi_name'],
            $A['jobname'],
            $A,
            $output,
            $svc_msg
        );
        // Set the final job completion status
        self::updateJobStatus($A['id'], $status);
        return $status;
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
        global $_TABLES;

        $sql = '';
        $jobid = (int)$jobid;
        switch ($status) {
        case self::RUNNING:
            $status = 'running';
            $sql = ", started = UNIX_TIMESTAMP()";
            break;
        case PLG_RET_OK:
            $status = 'completed';
            $sql = ", completed = UNIX_TIMESTAMP()";
            break;
        case PLG_RET_ERROR:
            $status = 'plugin_error';
            break;
        default:
            $status = 'unknown';
            break;
        }

        $sql = "UPDATE {$_TABLES['lglib_jobqueue']}
            SET status = '{$status}' $sql
            WHERE id = $jobid";
        DB_query($sql);
    }


    /**
     * Queue Admin List View.
     *
     * @return  string      HTML for the product list.
     */
    public static function adminList()
    {
        global $_CONF, $_LGLIB_CONF, $_TABLES, $LANG_LGLIB, $_USER, $LANG_ADMIN, $LANG_LGLIB_HELP;

        USES_lib_admin();

        $display = '';
        $sql = "SELECT * FROM {$_TABLES['lglib_jobqueue']}";
        $header_arr = array(
            array(
                'text'  => 'ID',
                'field' => 'id',
                'sort'  => true,
                'align' => 'right',
            ),
            array(
                'text'  => 'Plugin',
                'field' => 'pi_name',
                'sort'  => true,
            ),
            array(
                'text'  => 'Job Name',
                'field' => 'jobname',
                'sort'  => true,
            ),
            array(
                'text'  => 'Submitted',
                'field' => 'submitted',
                'sort'  => true,
            ),
            array(
                'text'  => 'Started',
                'field' => 'started',
                'sort'  => true,
            ),
            array(
                'text'  => 'Completed',
                'field' => 'completed',
                'sort'  => true,
            ),
            array(
                'text'  => 'Status',
                'field' => 'status',
                'sort'  => true,
            ),
            array(
                'text'  => 'Delete',
                'field' => 'delete',
                'sort'  => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array(
            'field' => 'submitted',
            'direction' => 'asc',
        );

        $query_arr = array(
            'table' => 'lglib_jobqueue',
            'sql' => $sql,
            'query_fields' => array('pi_name', 'jobname'),
            'default_filter' => 'WHERE 1=1',
        );

        $chkactions = '<button type="submit" class="uk-button uk-button-mini uk-button-success" ' .
            'href="' . LGLIB_ADMIN_URL . '/index.php" name="runjobs">' .
            $LANG_LGLIB['run'] . '</button>';
        $chkactions .= '&nbsp;<button type="submit" class="uk-button uk-button-mini uk-button-danger" ' .
            'href="' . LGLIB_ADMIN_URL . '/index.php" name="delbutton_x">' .
            $LANG_ADMIN['delete'] . '</button>';
        $options = array(
            'chkdelete' => 'true',
            'chkfield' => 'id',
            'chkname' => 'id',
            'chkactions' => $chkactions,
        );

        $text_arr = array(
            'has_extras' => true,
            'form_url' => LGLIB_ADMIN_URL . '/index.php?jobqueue',
        );

        $outputHandle = \outputHandler::getInstance();
        $outputHandle->addLinkScript(LGLIB_URL . '/js/admin.js');

        $T = new \Template(LGLIB_PI_PATH . '/templates/admin/');
        $T->set_file('queuelist', 'queue_list.thtml');
        $T->set_var(
            'queue_list',
            ADMIN_list(
                'shop_jobqueue_list',
                array(__CLASS__,  'getAdminField'),
                $header_arr, $text_arr, $query_arr, $defsort_arr,
                '', '', $options, ''
            )
        );
        $T->parse('output', 'queuelist');
        return $T->finish($T->get_var('output'));//$display;
    }


    /**
     * Get an individual field for the queue admin list.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @return  string              HTML for field display in the table
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_SHOP_CONF, $LANG_SHOP, $LANG_ADMIN;

        $retval = '';

        switch($fieldname) {
        case 'submitted':
        case 'started':
        case 'completed':
            $dt = new \Date($fieldvalue, $_CONF['timezone']);
            $retval = $dt->toMySQL(true);
            break;
        case 'status':
            $retval = FieldList::select(array(
                'name' => 'q_stat[' . $A['id'] . ']',
                'onchange' => "LGLIB_setQstatus('" . $A['id'] . "', this.value)",
                'options' => array(
                    'ready' => array(
                        'selected' => $fieldvalue == 'ready',
                        'value' => 'ready',
                    ),
                    'running' => array(
                        'selected' => $fieldvalue == 'running',
                        'value' => 'running',
                    ),
                    'completed' => array(
                        'selected' => $fieldvalue == 'completed',
                        'value' => 'completed',
                    ),
                    'plugin_error' => array(
                        'selected' => $fieldvalue == 'plugin_error',
                        'value' => 'plugin_error',
                    ),
                ),
            ) );
            break;

        case 'delete':
            $retval = FieldList::delete(array(
                'delete_url' => LGLIB_ADMIN_URL . '/index.php?deljob=' . $A['id'],
            ) );
            /*
            $retval = COM_createLink(
                $icon_arr['delete'],
                LGLIB_ADMIN_URL . '/index.php?deljob=' . $A['id']
            );*/
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    public static function deleteJobs($ids=array())
    {
        global $_TABLES;

        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $ids = implode(',', $ids);
        $sql = "DELETE FROM {$_TABLES['lglib_jobqueue']}
            WHERE id IN ($ids)";
        DB_query($sql);
    }


    /**
     * Purge completed jobs from the queue after a number of days.
     *
     * @param   integer $days   Number of days to keep completed jobs
     */
    public static function purgeCompleted($days=0)
    {
        global $_TABLES;

        $ts = time() + ($days * 86400);
        $sql = "DELETE FROM {$_TABLES['lglib_jobqueue']}
            WHERE status = 'completed'
            AND completed IS NOT NULL
            AND completed < $ts";
        DB_query($sql);
    }


    /**
     * Set the status for a single item.
     *
     * @param   integer $q_id       Queue record ID
     * @param   string  $newstatus  New status to set
     * @return  boolean     True on success, False on error.
     */
    public static function setStatus($q_id, $newstatus)
    {
        global $_TABLES;

        $q_id = (int)$q_id;
        $newstatus = DB_escapeString($newstatus);
        $sql = "UPDATE {$_TABLES['lglib_jobqueue']}
            SET status = '$newstatus'
            WHERE id = $q_id";
        DB_query($sql);
        return DB_error() ? false : true;
    }

}
