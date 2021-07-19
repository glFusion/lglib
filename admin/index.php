<?php
/**
 * Admin functions for the lgLib plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2021 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

$display = '';
$pi_title = $_LGLIB_CONF['pi_display_name'] . ' ' .
            $LANG32[36] . ' ' . $_LGLIB_CONF['pi_version'];
LGLIB_setGlobal('pi_title', $pi_title);

// If user isn't a root user or if the backup feature is disabled, bail.
// Also if using glFusion > 1.6.0, which includes the backup function.
if (!SEC_inGroup('Root')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the lglib admin page.");
    COM_404();
    exit;
}

$content = '';
$action = 'jobqueue';
$expected = array(
    // Actions to perform
    'runjobs', 'delbutton_x', 'purgecomplete', 'deljob', 'flushqueue',
    // Views to display
    'jobqueue',
);

foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

switch ($action) {
case 'flushqueue':
    $errors = LGLib\JobQueue::run();
    $msg = 'Completed';
    $msg_status = 'info';
    if ($errors > 0) {
        $msg .= sprintf(' - with %d errors', $errors);
        $msg_status = 'error';
    }
    COM_setMsg($msg, $msg_status);
    COM_refresh(LGLIB_ADMIN_URL . '/index.php?jobqueue');
    break;
    
case 'purgecomplete':
    LGLib\JobQueue::purgeCompleted();
    COM_refresh(LGLIB_ADMIN_URL . '/index.php?jobqueue');
    break;

case 'runjobs':
    LGLib\JobQueue::runById($_POST['id']);
    COM_refresh(LGLIB_ADMIN_URL . '/index.php?jobqueue');
    break;

case 'deljob':
    LGLib\JobQueue::deleteJobs($actionval);
    COM_refresh(LGLIB_ADMIN_URL . '/index.php?jobqueue');
    break;

case 'delbutton_x':
    if (isset($_POST['id']) && is_array($_POST['id'])) {
        LGLib\JobQueue::deleteJobs($_POST['id']);
    }
    COM_refresh(LGLIB_ADMIN_URL . '/index.php?jobqueue');
    break;

case 'jobqueue':
default:
    $content = LGLib\JobQueue::adminList();
    break;
}

$display .= COM_siteHeader('menu', $pi_title);
$display .= LGLib\Menu::Admin($action);
$display .= $content;
$display .= COM_siteFooter();
echo $display;
