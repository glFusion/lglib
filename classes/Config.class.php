<?php
/**
 * Class to read and manipulate configuration values for GLib.
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
 * Class to get plugin configuration data.
 * @package lglib
 */
final class Config
{
    /** Plugin Name.
     */
    public const PI_NAME = 'lglib';

    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;


    /**
     * Get the configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }


    /**
     * Create an instance of the configuration object.
     */
    private function __construct()
    {
        global $_SYSTEM, $_CONF;  // for base urls

        if ($this->properties === NULL) {
            $this->properties = \config::get_instance()
                ->get_config(self::PI_NAME);
        }
        $this->properties['pi_name'] = self::PI_NAME;
        $this->properties['pi_display_name'] = 'lgLib';
        $this->properties['url'] = $_CONF['site_url'] . '/' . self::PI_NAME;
        $this->properties['admin_url'] = $_CONF['site_admin_url'] . '/plugins/' . self::PI_NAME;
        $this->properties['path'] = $_CONF['path'] . 'plugins/' . self::PI_NAME . '/';
        $this->properties['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? true : false;
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key    Name of item to retrieve
     * @return  mixed       Value of config item
     */
    public function _get($key=NULL, $default=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } elseif (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
           return $default;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     * @return  object  $this
     */
    public function _set($key, $val)
    {
        if ($val === NULL) {
            unset($this->properties[$key]);
        } else {
            $this->properties[$key] = $val;
        }
        return $this;
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set, NULL to unset
     */
    public static function set($key, $val=NULL)
    {
        return self::getInstance()->_set($key, $val);
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key    Name of item to retrieve
     * @return  mixed       Value of config item
     */
    public static function get($key=NULL, $default=NULL)
    {
        return self::getInstance()->_get($key, $default);
    }


    /**
     * Convenience function to get the base plugin path.
     *
     * @return  string      Path to main plugin directory.
     */
    public static function path()
    {
        return self::_get('path');
    }


    /**
     * Convenience function to get the path to plugin templates.
     *
     * @return  string      Template path
     */
    public static function path_template()
    {
        return self::get('path') . 'templates/';
    }

}
