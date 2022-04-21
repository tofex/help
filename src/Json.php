<?php

namespace Tofex\Help;

use Exception;
use JsonSerializable;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Json
{
    /** @var Variables */
    protected $variableInterface;

    /**
     * @param Variables $variableHelper
     */
    public function __construct(Variables $variableHelper)
    {
        $this->variableInterface = $variableHelper;
    }

    /**
     * @param string|null $encodedValue
     *
     * @return mixed
     */
    public function decode(?string $encodedValue)
    {
        if ( ! $this->variableInterface->isEmpty($encodedValue)) {
            return json_decode($encodedValue, true);
        }

        return null;
    }

    /**
     * @param mixed $valueToEncode
     * @param bool  $unescaped
     * @param bool  $pretty
     * @param bool  $checkEncoding
     *
     * @return string
     */
    public function encode($valueToEncode, bool $unescaped = false, bool $pretty = false, bool $checkEncoding = false)
    {
        if ($checkEncoding) {
            $checkValue = is_array($valueToEncode) ? $valueToEncode : [$valueToEncode];
            try {
                $this->checkEncoding($checkValue);
            } catch (Exception $exception) {
                return false;
            }
        }

        $options = 0;

        if ($unescaped) {
            $options = $options | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        }

        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        return $options > 0 ? json_encode($valueToEncode, $options) : json_encode($valueToEncode);
    }

    /**
     * @param array  $array
     * @param string $parentPath
     *
     * @throws Exception
     */
    public function checkEncoding(array $array, string $parentPath = '')
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->checkEncoding($value, empty($parentPath) ? $key : ($parentPath . ':' . $key));
                continue;
            } else if (is_object($value)) {
                if ($value instanceof JsonSerializable) {
                    $value = $value->jsonSerialize();
                } else {
                    if (method_exists($value, '__toString')) {
                        $value = (string)$value;
                    } else {
                        continue;
                    }
                }
            }

            if (mb_detect_encoding($value, null, true) === false) {
                throw new Exception(sprintf('Invalid encoding found in path: %s with value: %s',
                    empty($parentPath) ? $key : ($parentPath . ':' . $key), $value));
            }
        }
    }
}
