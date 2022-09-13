<?php
/**
 * Configuration Defaults for the lgLib plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012 Lee Garner
 * @package     lglib
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @var global config data */
global $lglibConfigData;
$lglibConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'cal_style',
        'default_value' => 'blue',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 14,
        'sort' => 10,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'img_disp_relpath',
        'default_value' => 'data/imgcache',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 20,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'cron_schedule_interval',
        'default_value' => '0',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 30,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'cron_key',
        'default_value' => md5(time() . rand()),
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 40,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'img_cache_maxage',
        'default_value' => '90',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 50,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'img_cache_interval',
        'default_value' => '120',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 60,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'slimbox_autoactivation',
        'default_value' => '0',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 70,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'use_lglib_messages',
        'default_value' => '0',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 80,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'enable_smartresizer',
        'default_value' => '0',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 90,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'queue_cron',
        'default_value' => '0',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 100,
        'set' => true,
        'group' => 'lglib',
    ),
    array(
        'name' => 'queue_purge_completed',
        'default_value' => '30',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 110,
        'set' => true,
        'group' => 'lglib',
    ),
);

/**
 * Initialize lgLib plugin configuration.
 *
 * @return  boolean     true: success; false: an error occurred
 */
function plugin_initconfig_lglib()
{
    global $lglibConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('lglib')) {
        USES_lib_install();
        foreach ($lglibConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: LGLib config group already exists');
    }
    return true;
}
