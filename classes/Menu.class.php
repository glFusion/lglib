<?php
/**
 * Class to provide admin menus for the LGLib plugin.
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
namespace LGLib;


/**
 * Class to provide admin menus.
 * @package lglib
 */
class Menu
{
    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_ADMIN, $LANG_LGLIB;

        USES_lib_admin();

        $retval = '';
        $menu_arr = array(
            array(
                'url' => LGLIB_ADMIN_URL . '/index.php?jobqueue',
                'text' => $LANG_LGLIB['manage_queue'],
                'active' => $view == 'jobqueue' ? true : false,
            ),
            array(
                'url'  => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home'],
            ),
        );

        $retval .= \ADMIN_createMenu(
            $menu_arr,
            '',
            plugin_geticon_lglib()
        );
        return $retval;
    }

}
