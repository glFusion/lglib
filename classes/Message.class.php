<?php
/**
 * Class to handle storang and displaying messages.
 * Saves messages in the database to display to the specified user
 * at a later time.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020-2021 <lee@leegarner.com>
 * @package     lglib
 * @version     v1.0.13
 * @since       v1.0.12
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace LGLib;


/**
 * Class to handle messages stored for later display.
 * @package lglib
 */
class Message
{
    /** Message store type. `db` or `session`.
     * @const string */
    const MSG_STORE = 'db';

    /** Session variable name for messages, if MSG_STORE is "session".
     * @const string */
    const MSG_VAR = 'lgmessages';

    /** Target user ID.
     * @var integer */
    private $uid = 1;

    /** Message level, default "info".
     * @var integer */
    private $level = 1;

    /** Plugin-supplied code.
     * @var string */
    private $pi_code = '';

    /** Message title.
     * @var string */
    private $title = '';

    /** Flag for the message to persist or disappear.
     * @var boolean */
    private $persist = 0;

    /** Message text.
     * @var string */
    private $message = '';

    /** Expiration date.
     * @var string */
    private $expires = '';

    /** Session ID, set for anonymous users.
     * @var string */
    private $sess_id = '';


    /**
     * Set default values.
     */
    public function __construct()
    {
        $this->title = MO::_('System Message');
        $this->withUid(1);
    }


    /**
     * Check if a specific message exists.
     * Looks for the user ID, session ID and plugin code.
     *
     * @return  string      Message text, empty if not found
     */
    public function exists()
    {
        global $_TABLES;

        $where = 'uid = ' . (int)$this->uid;
        if (!empty($this->sess_id)) {
            $where .= " AND sess_id = '" . DB_escapeString($this->sess_id) . "'";
        }
        if (!empty($this->pi_code)) {
            $where .= " AND pi_code = '" . DB_escapeString($this->pi_code) . "'";
        }
        return DB_getItem(
            $_TABLES['lglib_messages'],
            'message',
            $where
        );
    }


    /**
     * Store a message in the database that can be retrieved later.
     * This provides a more flexible method for showing popup messages
     * than the numbered-message method.
     *
     * @param   array|string    $args   Message to be displayed, or argument array
     * @param   string  $title      Optional title
     * @param   boolean $persist    True if the message should persist onscreen
     * @param   string  $expires    SQL-formatted expiration datetime
     * @param   string  $pi_code    Name of plugin storing the message
     * @param   integer $uid        ID of the user to view the message
     * @param   boolean $use_sess_id    True to use session ID to retrieve
     */
    public function store()
    {
        global $_USER;

        if (empty($this->message)) {
            return;
        }

        if (self::MSG_STORE == 'db') {
            global $_TABLES;
            $sql = "INSERT INTO {$_TABLES['lglib_messages']} SET
                uid = '{$this->uid}',
                sess_id = '" . DB_escapeString($this->sess_id) . "',
                title = '" . DB_escapeString($this->title) . "',
                message = '" . DB_escapeString($this->message) . "',
                persist = '{$this->persist}',
                expires = " . $this->getExpiresDB() . ",
                pi_code = '" . DB_escapeString($this->pi_code) . "',
                level = '{$this->level}'";
            DB_query($sql, 1);
            if (DB_error()) {
                COM_errorLog("lglib: storeMessage SQL error: $sql");
            }
        } else {
            // Session-based messages can't be saved for another user.
            if ($this->uid > 0 && $this->uid != $_USER['uid']) {
                return;
            }
            // Make sure the session variable is available.
            if (!isset($_SESSION[self::MSG_VAR])) {
                $_SESSION[self::MSG_VAR] = array();
            }
            $_SESSION[self::MSG_VAR][] = array(
                'title' => $ths->title,
                'message' => $this->message,
                'persist' => $this->persist,
            );
        }
    }


    /**
     * Display all messagse for a user's session.
     * If $persist is true, or any $msg['persist'] == true, then the displayed
     * message will stay on the screen. Otherwise the message fades out.
     *
     * @param   boolean $persist    Keep the message box open? False = fade out
     * @return  string      HTML for message box
     */
    public static function showAll($persist = false)
    {
        $retval = '';

        self::expire();

        $msgs = self::getAll();
        if (empty($msgs)) {
            return '';
        }

        // Include a zero element in case level is undefined
        $levels = array('info', 'success', 'info', 'warning', 'error');
        $persist = false;

        if (count($msgs) == 1) {
            $message = $msgs[0]['message'];
            $title = $msgs[0]['title'];
            $level = $msgs[0]['level'];
            if ($msgs[0]['persist']) $persist = true;
        } else {
            $message = '';
            $title = '';
            $level = 1;     // Start at the "best" level
            foreach ($msgs as $msg) {
                $message .= '<li class="lglmessage">' .
                    $msg['message'] .
                    '</li>';
                // If any message requests "persist", then all persist
                if ($msg['persist']) $persist = true;
                // Set to the highest (worst) error level
                if ($msg['level'] > $level) $level = $msg['level'];
                // First title found in a message gets used instead of default
                if (empty($title) && !empty($msg['title'])) $title = $msg['title'];
            }
            $message = '<ul class="lglmessage">' . $message . '</ul>';
        }
        self::deleteUser();
        // Revert to the system message title if no other title found
        if (empty($title)) {
            $title = MO::_('System Message');
        }
        $leveltxt = isset($levels[$level]) ? $levels[$level] : 'info';
        if ($persist) {
            $T = new \Template(__DIR__ . '/../templates');
            $T->set_file('msg', 'sysmessage.thtml');
            $T->set_var(array(
                'leveltxt' => $leveltxt,
                'message' => $message,
            ) );
            $T->parse('output', 'msg');
            return $T->finish($T->get_var('output'));
        } else {
            return COM_showMessageText($message, $title, $persist, $leveltxt);
        }
    }


    /**
     * Retrieve all messages for display.
     * Gets all messages from the DB where the user ID matches for
     * non-anonymous users, OR the session ID matches. This allows a message
     * caused by an anonymous action to be displayed to the user after login.
     *
     * @return  array   Array of messages, title=>message
     */
    public static function getAll()
    {
        global $_TABLES, $_USER;

        $messages = array();

        if (self::MSG_STORE == 'db') {
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
            $messages = SESS_getVar(self::MSG_VAR);
            if ($messages == 0) $messages = array();
        }
        return $messages;
    }


    /**
     * Delete expired messages.
     * Only applies when using DB as the message store.
     */
    public static function expire()
    {
        if (self::MSG_STORE == 'db') {
            global $_TABLES;
            $sql = "DELETE FROM {$_TABLES['lglib_messages']}
                WHERE expires < NOW()";
            DB_query($sql, 1);
        }
    }


    /**
     * Delete a single message.
     * Called by plugins to remove a message placed earlier. At least one of
     * $uid or $pi_code must be present
     *
     * @param   integer $uid    User ID, required, can be zero to ignore
     * @param   string  $pi_code    Optional plugin code value.
     */
    public static function deleteOne($uid, $pi_code = '')
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
     * Delete all messages for a user.
     * Checks for messages where the session ID matches the current session,
     * or the user ID matches for logged-in users.
     */
    public static function deleteUser()
    {
        global $_USER;

        if (self::MSG_STORE == 'session') {
            SESS_setVar(self::MSG_VAR, array());
        } else {
            // delete messages for the user or session that have not expired.
            global $_TABLES, $_USER;
            $uid = (int)$_USER['uid'];
            $q = array(
                "sess_id = '" . DB_escapeString(session_id()) . "'",
            );
            if ($uid > 1) {
                $q[] = "uid = $uid";
            }
            if (!empty($q)) {
                $query = '(' . implode(' OR ', $q) . ')';
                $sql = "DELETE FROM {$_TABLES['lglib_messages']} WHERE $query";
                $result = DB_query($sql);
                DB_delete($_TABLES['lglib_messages'], 'uid', $_USER['uid']);
            }
        }
    }


    /**
     * Set the message level (info, error, etc).
     * Several options can be supplied for the level values.
     *
     * @param   string  $level  Message level.
     * @return  object  $this
     */
    public function withLevel($level)
    {
        switch ($level) {
        case 'error':
        case 'err':
        case false:
        case 'alert':
        case 4:
            $this->level = 4;
            break;
        case 'warn':
        case 'warning':
        case 3:
            $this->level = 3;
            break;
        case 'info':
        case 2:
        default:
            $this->level = 2;
            break;
        case 'success':
        case 1:
            $this->level = 1;
            break;
        }
        return $this;
    }


    /**
     * Set the ID of the user who should view the message.
     *
     * @param   integer $uid    User ID
     * @return  object  $this
     */
    public function withUid($uid)
    {
        global $_USER;

        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $this->uid = (int)$uid;
        $this->withSessId($this->uid < 2);
        return $this;
    }


    /**
     * Set the plugin code.
     * This may be the plugin name or other optional ID.
     *
     * @param   string  $pi_code    Plugin-supplied code
     * @return  object  $this
     */
    public function withPiCode($pi_code)
    {
        $this->pi_code = $pi_code;
        return $this;
    }


    /**
     * Set the flag to determine if the message stays on-screen.
     *
     * @param   boolean $persist    True to persist, False to disappear
     * @return  object  $this
     */
    public function withPersists($persist)
    {
        $this->persist = $persist ? 1 : 0;
        return $this;
    }


    /**
     * Set the message text to display.
     *
     * @param   string  $msg    Message text
     * @return  object  $this
     */
    public function withMessage($msg)
    {
        $this->message = $msg;
        return $this;
    }


    /**
     * Set the message title.
     *
     * @param   string  $title  Title to be displayed
     * @return  object  $this
     */
    public function withTitle($title)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * Set the expiration date.
     *
     * @param   string  $exp    Expiration Date, YYYY-MM-DD
     * @return  object  $this
     */
    public function withExpires($exp)
    {
        $this->expires = $exp;
        return $this;
    }


    /**
     * Use the session ID, used for anonymous users.
     *
     * @param   boolean $flag   True to use the session ID
     * @return  object  $this
     */
    public function withSessId($flag)
    {
        $this->sess_id = $flag ? session_id() : '';
        return $this;
    }


    /**
     * Get the expiration date to be saved in the database.
     * The default is 4 hours from now for anonymous users.
     *
     * @return  string      Date string to save in the database
     */
    public function getExpiresDB()
    {
        if (empty($this->expires)) {
            if ($this->uid < 2) {
                // Anonymous messages expire in 4 hours
                $expires = 'DATE_ADD(NOW(), INTERVAL 4 HOUR)';
            } else {
                // Member messages exist until viewed
                $expires = "'2037-12-31'";
            }
        } else {
            $expires = "'" . $this->expires . "'";
        }
        return $expires;
    }

}

