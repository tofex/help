<?php

namespace Tofex\Help;

use stdClass;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Arrays
{
    /** @var Variables */
    protected $variableHelper;

    /** @var Strings */
    protected $stringHelper;

    /**
     * @param Variables|null $variableHelper
     * @param Strings|null   $stringHelper
     */
    public function __construct(Variables $variableHelper = null, Strings $stringHelper = null)
    {
        if ($variableHelper === null) {
            $variableHelper = new Variables();
        }

        $this->variableHelper = $variableHelper;

        if ($stringHelper === null) {
            $stringHelper = new Strings();
        }

        $this->stringHelper = $stringHelper;
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    public function mergeArrays(array $array1, array $array2): array
    {
        $allKeysNumeric = true;

        $config1Keys = array_keys($array1);
        $config2Keys = array_keys($array2);

        foreach ($config1Keys as $configKey) {
            if ( ! is_numeric($configKey)) {
                $allKeysNumeric = false;
                break;
            }
        }

        if ($allKeysNumeric) {
            foreach ($config2Keys as $configKey) {
                if ( ! is_numeric($configKey)) {
                    $allKeysNumeric = false;
                    break;
                }
            }
        }

        if ($allKeysNumeric) {
            return array_merge($array1, $array2);
        }

        $combined = [];

        foreach ($array1 as $key1 => $value1) {
            $value2 = array_key_exists($key1, $array2) !== false ? $array2[ $key1 ] : null;

            if (( ! is_scalar($value1) && ! is_array($value1)) || ( ! is_scalar($value2) && ! is_array($value2))) {
                $combined[ $key1 ] = $value1;

                continue;
            }

            if (is_scalar($value1)) {
                if (is_array($value2)) {
                    $combined[ $key1 ] = $this->mergeArrays([$value1], $value2);
                } else {
                    $combined[ $key1 ] = $value2;
                }

                continue;
            }

            if (is_array($value1)) {
                if (is_scalar($value2)) {
                    $combined[ $key1 ] = $value1;
                    $combined[ $key1 ][] = $value2;

                    continue;
                }

                if (is_array($value2)) {
                    $combined[ $key1 ] = $this->mergeArrays($value1, $value2);
                }
            }
        }

        foreach ($array2 as $key2 => $value2) {
            if ( ! is_numeric($key2) && ! array_key_exists($key2, $combined) !== false) {
                if (preg_match('/(.*)\+$/', $key2, $matches)) {
                    $key2 = array_key_exists(1, $matches) ? $matches[ 1 ] : null;

                    if (array_key_exists($key2, $combined) !== false) {
                        $combinedValue = $combined[ $key2 ];

                        if ( ! is_array($combinedValue)) {
                            $combined[ $key2 ] = [$combinedValue];
                        }

                        $combined[ $key2 ][] = $value2;
                    } else {
                        $combined[ $key2 ] = $value2;
                    }
                } else if (preg_match('/(.*)\-$/', $key2, $matches)) {
                    $key2 = array_key_exists(1, $matches) ? $matches[ 1 ] : null;

                    if (array_key_exists($key2, $combined) !== false) {
                        $combinedValue = $combined[ $key2 ];

                        if (is_array($combinedValue)) {
                            foreach ($combinedValue as $combinedValueKey => $combinedValueValue) {
                                if ($combinedValueValue === $value2) {
                                    unset($combinedValue[ $combinedValueKey ]);
                                }
                            }

                            $combined[ $key2 ] = $combinedValue;
                        } else {
                            if ($combinedValue === $value2) {
                                unset($combined[ $key2 ]);
                            }
                        }
                    }
                } else {
                    $combined[ $key2 ] = $value2;
                }
            }
        }

        return $combined;
    }

    /**
     * @param stdClass $stdClassObject
     *
     * @return array
     */
    public function stdClassToArray(stdClass $stdClassObject): array
    {
        $array = ( array )$stdClassObject;

        return $this->stdClassToArrayCheckValues($array);
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function stdClassToArrayCheckValues(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof stdClass) {
                $array[ $key ] = $this->stdClassToArray($value);
            } else if (is_array($value)) {
                $array[ $key ] = $this->stdClassToArrayCheckValues($value);
            } else {
                $array[ $key ] = $value;
            }
        }

        return $array;
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getDirectValue(array $array, string $key, $defaultValue = null)
    {
        return array_key_exists($key, $array) ? $array[ $key ] : $defaultValue;
    }

    /**
     * @param array       $array
     * @param string      $key
     * @param mixed       $defaultValue
     * @param bool        $splitKey
     * @param string|null $checkedValue
     *
     * @return mixed
     */
    public function getValue(
        array $array,
        string $key,
        $defaultValue = null,
        bool $splitKey = true,
        string $checkedValue = null)
    {
        if (empty($array) || (empty($key) && ! $key == 0)) {
            return $defaultValue;
        }

        if ( ! $splitKey && array_key_exists($key, $array)) {
            return $array[ $key ];
        }

        $keys = $splitKey ? preg_split('/:/', $key) : [$key];

        if ( ! empty($keys)) {
            $firstKey = trim(array_shift($keys));

            if ($checkedValue && count($keys) === 0) {
                if (array_key_exists($firstKey, $array) && $array[ $firstKey ] == $checkedValue) {
                    return $array;
                }

                foreach ($array as $arrayItem) {
                    if (is_array($arrayItem) && array_key_exists($firstKey, $arrayItem) &&
                        $arrayItem[ $firstKey ] == $checkedValue) {
                        return $arrayItem;
                    }
                }
            } else if (preg_match('/^\[([\w_-]+)=(.+)\]$/', $firstKey, $matches)) {
                $valueKey = array_key_exists(1, $matches) ? $matches[ 1 ] : null;
                $valueValue = array_key_exists(2, $matches) ? $matches[ 2 ] : null;

                foreach ($array as $arrayKey => $arrayValue) {
                    if (is_array($arrayValue)) {
                        $valueKeys = array_keys($arrayValue);

                        foreach ($valueKeys as $nextValueKey) {
                            if (strcasecmp($nextValueKey, $valueKey) == 0 &&
                                array_key_exists($nextValueKey, $arrayValue) &&
                                $arrayValue[ $nextValueKey ] == $valueValue) {
                                if (count($keys) > 0) {
                                    return $this->getValue($arrayValue, join(':', $keys), $defaultValue);
                                } else {
                                    return $arrayValue;
                                }
                            }
                        }
                    } else if (strcasecmp($arrayKey, $valueKey) == 0 && $valueValue == $arrayValue) {
                        if (count($keys) > 0) {
                            return $this->getValue($array, join(':', $keys), $defaultValue);
                        } else {
                            return $array;
                        }
                    }
                }
            } else {
                if ( ! $this->isAssociative($array) && ! is_numeric($firstKey)) {
                    $result = [];
                    foreach ($array as $arrayValue) {
                        if (is_array($arrayValue)) {
                            $arrayResult = $this->getValue($arrayValue, $key, null, $splitKey, $checkedValue);
                            if ($arrayResult !== null) {
                                $result[] = $arrayResult;
                            }
                        }
                    }
                    return empty($result) ? $defaultValue : $result;
                } else {
                    $arrayKeys = array_keys($array);

                    foreach ($arrayKeys as $arrayKey) {
                        if (strcasecmp($arrayKey, $firstKey) == 0) {
                            $result = array_key_exists($arrayKey, $array) ? $array[ $arrayKey ] : null;

                            if (is_array($result) && count($keys) > 0) {
                                return $this->getValue($result, join(':', $keys), $defaultValue, $splitKey,
                                    $checkedValue);
                            } else if (count($keys) === 0) {
                                return $result;
                            } else {
                                return $defaultValue;
                            }
                        }
                    }
                }
            }
        }

        return $defaultValue;
    }

    /**
     * @param array  $array
     * @param string $value
     * @param mixed  $defaultValue
     *
     * @return  int|string|mixed
     */
    public function getKey(array $array, string $value, $defaultValue = null)
    {
        foreach ($array as $key => $nextValue) {
            if (strcasecmp($nextValue, $value) == 0) {
                return $key;
            }
        }

        return $defaultValue;
    }

    /**
     * @param mixed $value
     * @param array $array
     * @param bool  $caseSensitive
     *
     * @return bool
     */
    public function inArray($value, array $array, bool $caseSensitive = false): bool
    {
        $arrayCopy = $this->arrayCopy($array);

        if ($caseSensitive === false) {
            $function = function ($useInput) {
                return is_string($useInput) ? strtolower($useInput) : $useInput;
            };

            $arrayCopy = array_map($function, $arrayCopy);
        }

        return in_array(is_string($value) && $caseSensitive === false ? strtolower($value) : $value, $arrayCopy);
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function arrayCopy(array $array): array
    {
        $copy = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $copy[ $key ] = $this->arrayCopy($value);
            } else if (is_object($value)) {
                $copy[ $key ] = clone $value;
            } else {
                $copy[ $key ] = $value;
            }
        }

        return $copy;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function isAssociative(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        for ($iterator = count($array) - 1; $iterator; $iterator--) {
            if ( ! array_key_exists($iterator, $array)) {
                return true;
            }
        }

        return ! array_key_exists(0, $array);
    }

    /**
     * @param array  $array
     * @param string $lineBreak
     * @param int    $level
     *
     * @return string
     */
    public function output(array $array, string $lineBreak = "\n", int $level = 0): string
    {
        $output = '';

        foreach ($array as $key => $value) {
            if ( ! empty($output)) {
                $output .= $lineBreak;
            }

            $output .= str_repeat('    ', $level) . $key . ': ';

            if (is_array($value)) {
                $valueOutput = $this->output($value, $lineBreak, $level + 1);

                if ( ! empty($valueOutput)) {
                    $output .= $lineBreak . $valueOutput;
                }
            } else if (is_object($value) && is_callable([
                    $value,
                    '__toArray'
                ])) {

                $valueOutput = $this->output($value->__toArray(), $lineBreak, $level + 1);

                if ( ! empty($valueOutput)) {
                    $output .= $lineBreak . $valueOutput;
                }
            } else {
                $value = ( string )$value;

                if (strlen($value) > 4096) {
                    $value = '[' . strlen($value) . ' bytes]';
                }

                $output .= $value;
            }
        }

        return $output;
    }

    /**
     * @param array  $array
     * @param string $regex
     *
     * @return array
     */
    public function getAllValues(array $array, string $regex): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (preg_match('/' . $regex . '/', $key)) {
                $result[ $key ] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param array $keys
     * @param mixed $value
     * @param bool  $overwrite
     *
     * @return array
     */
    public function addDeepValue(array $array, array $keys, $value, bool $overwrite = true): array
    {
        if (empty($keys)) {
            return $array;
        }

        if (count($keys) > 1) {
            $key = array_shift($keys);
            $firstArray = array_key_exists($key, $array) ? $array[ $key ] : [];
            if ( ! is_array($firstArray)) {
                $array[ $key ] = [$firstArray, $this->addDeepValue([], $keys, $value, $overwrite)];
            } else {
                $array[ $key ] = $this->addDeepValue($firstArray, $keys, $value, $overwrite);
            }
        } else {
            $key = array_shift($keys);
            if ($overwrite) {
                $array[ $key ] = $value;
            } else {
                if (array_key_exists($key, $array)) {
                    if ( ! is_array($array[ $key ])) {
                        $array[ $key ] = [$array[ $key ]];
                    }
                    $array[ $key ][] = $value;
                } else {
                    $array[ $key ] = $value;
                }
            }
        }

        return $array;
    }

    /**
     * Method to filter empty elements from XML
     *
     * @param array $array
     *
     * @return array
     */
    public function arrayFilterRecursive(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                /** @var array $value */
                $value = count($value) === 1 && array_key_exists(0, $value) && is_string($value[ 0 ]) &&
                $this->variableHelper->isEmpty(trim($value[ 0 ])) ? $value[ 0 ] : $this->arrayFilterRecursive($value);
            }
        }

        return array_filter($array, function ($value) {
            return ! $this->variableHelper->isEmpty($value) &&
                ! (is_string($value) && $this->variableHelper->isEmpty(trim($value)));
        });
    }

    /**
     * Returns the last element of the array
     *
     * @param array $array
     *
     * @return mixed
     */
    public function getLast(array $array)
    {
        return array_slice($array, -1)[ 0 ];
    }

    /**
     * @param array $array1
     * @param array $array2
     * @param bool  $strict
     *
     * @return array
     */
    public function arrayDiffRecursive(array $array1, array $array2, bool $strict = false): array
    {
        $result = [];

        foreach ($array1 as $key1 => $value1) {
            if (array_key_exists($key1, $array2)) {
                $value2 = $array2[ $key1 ];

                if (is_array($value1) || is_array($value2)) {
                    if (is_array($value1) && is_array($value2)) {
                        $valuesDiff = $this->arrayDiffRecursive($value1, $value2, $strict);

                        if ( ! empty($valuesDiff)) {
                            $result[ $key1 ] = $valuesDiff;
                        }
                    } else {
                        $result[ $key1 ] = $value2;
                    }
                } else {
                    if (($strict && $value1 !== $value2) || ( ! $strict && $value1 != $value2)) {
                        $result[ $key1 ] = $value2;
                    }
                }
            } else {
                $result[ $key1 ] = $value1;
            }
        }

        foreach ($array2 as $key2 => $value2) {
            if ( ! array_key_exists($key2, $array1)) {
                $result[ $key2 ] = $value2;
            }
        }

        return $result;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function cleanStrings(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[ $key ] = $this->cleanStrings($value);
            } else {
                if (is_string($value)) {
                    $array[ $key ] = $this->stringHelper->cleanString($value);
                }
            }
        }

        return $array;
    }
}
