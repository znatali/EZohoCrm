<?php

/**
 * EZohoCrm extension for Yii framework.
 *
 * API Reference Zoho CRM
 * @link https://www.zoho.com/crm/help/api/api-methods.html
 *
 * @author: Emile Bons <emile@emilebons.nl>
 * @link http://www.malvee.com
 * @link http://www.emilebons.nl
 * @copyright Copyright &copy; Emile Bons 2013
 * @license The MIT License
 * @category Yii 1.1
 * @package ext\EZohoCrm
 *
 * Extension was improved by
 * @author: Dmitry Kulikov <kulikovdn@gmail.com>
 */

namespace ext\EZohoCrm;

/**
 * EUtils contains useful functions which don't relate with Zoho CRM API or Yii directly.
 */
class EUtils
{
    /**
     * Getter for array items and object properties.
     * @param mixed $target array or object; number, string and null are also possible, but function will return default
     * value in these cases
     * @param mixed $path key of item in array or array of keys, name of property of object or array of names,
     * array of names of properties and keys of items
     * @param mixed $default default value
     * @return mixed value of found item or property or default value if item or property wasn't found.
     */
    public static function get($target, $path, $default = null)
    {
        if (!is_array($path) || empty($path)) {
            $path = array($path);
        }

        foreach ($path as $singleKey) {
            if (is_array($target) && array_key_exists($singleKey, $target)) {
                $target = $target[$singleKey];
            } elseif (is_object($target) && property_exists($target, $singleKey)) {
                $target = $target->$singleKey;
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * Setter for array items and object properties.
     * @param mixed $target array or object
     * @param mixed $path key of item in array or array of keys, name of property of object or array of names,
     * array of names of properties and keys of items
     * @param mixed $value value
     * @throws \Exception
     */
    public static function set(&$target, $path, $value)
    {
        if (!is_array($path) || empty($path)) {
            $path = array($path);
        }

        $targetReference = &$target;
        foreach ($path as $singleKey) {
            if (is_array($targetReference)) {
                if (array_key_exists($singleKey, $targetReference)) {
                    $targetReference = &$targetReference[$singleKey];
                } else {
                    throw new \Exception('Key with name "' . $singleKey . '" not found.');
                }
            } elseif (is_object($targetReference)) {
                if (property_exists($targetReference, $singleKey)) {
                    $targetReference = &$targetReference->$singleKey;
                } else {
                    throw new \Exception('Property with name "' . $singleKey . '" not found.');
                }
            } else {
                throw new \Exception('Item is not array or object.');
            }
        }

        $targetReference = $value;
    }

    /**
     * Returns human-readable description of last JSON error or null if there is no error.
     * @return null|string error description or null if there is no error.
     */
    public static function getJsonLastError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return null;
            case JSON_ERROR_DEPTH:
                return 'the maximum stack depth has been exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'invalid or malformed JSON';
            case JSON_ERROR_CTRL_CHAR:
                return 'control character error, possibly incorrectly encoded';
            case JSON_ERROR_SYNTAX:
                return 'syntax error';
            case JSON_ERROR_UTF8:
                return 'malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'unknown error';
        }
    }

    /**
     * Print dump information about a variable.
     * @param mixed $variable variable
     * @param bool $return if this parameter is set to true, function will return its output, instead of
     * printing it (which it does by default)
     * @return null|string dump information.
     */
    public static function printVarDump($variable, $return = false)
    {
        ob_start();
        // disable formatted var_dump by Xdebug
        $variables = array(
            'html_errors' => array('new' => 0),
            'xdebug.var_display_max_depth' => array('new' => -1),
            'xdebug.var_display_max_children' => array('new' => -1),
            'xdebug.var_display_max_data' => array('new' => -1),
        );
        foreach ($variables as $key => $value) {
            $variables[$key]['old'] = ini_get($key);
            ini_set($key, $value['new']);
        }
        var_dump($variable);
        foreach ($variables as $key => $value) {
            ini_set($key, $value['old']);
        }
        $result = ob_get_contents();
        ob_end_clean();
        if ($return) {
            return $result;
        } else {
            echo $result;
            return null;
        }
    }
}
