<?php
/**
 * List fields for the LGLib plugin.
 * Temporary until glFusion 2.0 is release.
 */
namespace LGLib;

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

class FieldList //extends \glFusion\FieldList
{
    /**
     * Return a cached template object to avoid repetitive path lookups.
     *
     * @return  object      Template object
     */
    protected static function init()
    {
        global $_CONF;

        static $t = NULL;

        if ($t === NULL) {
            $t = new \Template(Config::get('path') . '/templates/');
            $t->set_file('field', 'fieldlist.thtml');
        } else {
            $t->unset_var('output');
            $t->unset_var('attributes');
        }
        return $t;
    }


    public static function delete($args)
    {
        $t = self::init();
        $t->set_block('field','field-delete');

        if (isset($args['delete_url'])) {
            $t->set_var('delete_url',$args['delete_url']);
        } else {
            $t->set_var('delete_url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field-delete','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-delete',true);
        return $t->finish($t->get_var('output'));
    }


    public static function checkbox($args)
    {
        $t = self::init();
        $t->set_block('field','field-checkbox');

        // Go through the required or special options
        $t->set_block('field', 'attr', 'attributes');
        foreach ($args as $name => $value) {
            switch ($name) {
            case 'checked':
            case 'disabled':
                if ($value) {
                    $value = $name;
                } else {
                    continue 2;
                }
                break;
            }
            $t->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $t->parse('attributes', 'attr', true);
        }
        $t->parse('output', 'field-checkbox');
        return $t->finish($t->get_var('output'));
    }


    /**
     * Create a selection dropdown.
     * Options can be in a string named `option_list` or an array of
     * separate properties.
     *
     *  $opts = array(
     *      'name' => 'testoption',
     *      'onchange' => "alert('here');",
     *      'options' => array(
     *          'option1' => array(
     *              'disabled' => true,
     *              'value' => 'value1',
     *          ),
     *          'option2' => array(
     *              'selected' => 'something',
     *              'value' => 'value2',
     *          ),
     *          'option3' => array(
     *              'selected' => '',
     *              'value' => 'XXXXX',
     *          ),
     *      )
     *  );
     *
     *  @param  array   $args   Array of properties to use
     *  @return string      HTML for select element
     */
    public static function select($args)
    {
        if (!isset($args['options']) && !isset($args['option_list'])) {
            return '';
        }

        $t = self::init();
        $t->set_block('field','field-select');

        $def_opts = array(
            'value' => '',
            'selected' => false,
            'disabed' => false,
        );

        // Create the main selection element.
        $t->set_block('field', 'attr', 'attributes');
        foreach ($args as $name=>$value) {
            if ($name == 'options') {
                // Handle the options later
                continue;
            } elseif ($name == 'option_list') {
                // options were supplied as a string of <option> elements
                $t->set_var('option_list', $value);
            } else {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value,
                ) );
            }
            $t->parse('attributes', 'attr', true);
        }

        // Now loop through the options.
        if (isset($args['options']) && is_array($args['options'])) {
            $t->set_block('select', 'options', 'opts');
            foreach ($args['options'] as $name=>$data) {
                $t->set_var('opt_name', $name);
                // Go through the required or special options
                foreach ($def_opts as $optname=>$def_val) {
                    if (isset($data[$optname])) {
                        $t->set_var($optname, $data[$optname]);
                        unset($data[$optname]);
                    } else {
                        $t->set_var($optname, $def_val);
                    }
                }
                // Now go through the remaining supplied args for this option
                $str = '';
                foreach ($data as $name=>$value) {
                    $str .= "$name=\"$value\" ";
                }
                $t->set_var('other', $str);
                $t->parse('opts', 'options', true);
            }
        }
        $t->parse('output', 'field-select');
        $t->clear_var('opts');
        return $t->finish($t->get_var('output'));
    }            
            
}
