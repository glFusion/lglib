<?php
/**
 * Common admistrative AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     lglib
 * @version     v1.1.0
 * @since       v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_inGroup('Root')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the lglib admin ajax function.");
    $retval = array(
        'status' => false,
        'statusMessage' => $LANG_ACCESS['accessdenied'],
    );
    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    echo json_encode($retval);
    exit;
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = '';
}

switch ($action) {
case 'setQstatus':
    if (
        !empty($_POST['q_id']) &&
        !empty($_POST['newstatus'])
    ) {
        $newstatus = $_POST['newstatus'];
        $status = LGLib\JobQueue::setStatus($_POST['q_id'], $_POST['newstatus']);
        $retval = array(
            'status' => $status,
            'statusMessage' => $status ? $LANG_SHOP['msg_updated'] : $LANG_SHOP['msg_nochange'],
        );
    }
    break;
}

// Return the $retval array as a JSON string
header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate");
//A date in the past
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
echo json_encode($retval);
exit;
