<?php

namespace Tofex\Help;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Variables
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isEmpty($value): bool
    {
        return ! is_bool($value) && empty($value) &&
            (is_array($value) || is_object($value) || strlen(trim($value)) === 0);
    }

    /**
     * @param array $oldData
     * @param array $newData
     *
     * @return array
     */
    public function getChangedData(array $oldData, array $newData): array
    {
        if ($this->isEmpty($oldData)) {
            $changedAttributeCodes = $this->isEmpty($newData) ? [] : array_keys($newData);
        } else {
            $changedAttributeCodes = empty($oldData) ? $newData : [];

            foreach ($oldData as $oldDataAttributeCode => $oldDataAttributeValue) {
                if (strcasecmp($oldDataAttributeCode, 'updated_at') === 0) {
                    continue;
                }

                if ( ! array_key_exists($oldDataAttributeCode, $newData)) {
                    $changedAttributeCodes[] = $oldDataAttributeCode;
                } else {
                    $newDataAttributeValue = $newData[ $oldDataAttributeCode ];

                    if (is_scalar($oldDataAttributeValue) && is_scalar($newDataAttributeValue)) {
                        if (is_numeric($oldDataAttributeValue) && is_numeric($newDataAttributeValue)) {
                            if ((float)$oldDataAttributeValue != (float)$newDataAttributeValue) {
                                $changedAttributeCodes[] = $oldDataAttributeCode;
                            }
                        } else if (is_bool($oldDataAttributeValue) && is_bool($newDataAttributeValue)) {
                            if ($oldDataAttributeValue !== $newDataAttributeValue) {
                                $changedAttributeCodes[] = $oldDataAttributeValue;
                            }
                        } else if (is_bool($oldDataAttributeValue) && is_numeric($newDataAttributeValue)) {
                            if ($oldDataAttributeValue !== ($newDataAttributeValue !== 0)) {
                                $changedAttributeCodes[] = $oldDataAttributeValue;
                            }
                        } else if (is_numeric($oldDataAttributeValue) && is_bool($newDataAttributeValue)) {
                            if (($oldDataAttributeValue !== 0) !== $newDataAttributeValue) {
                                $changedAttributeCodes[] = $oldDataAttributeValue;
                            }
                        } else {
                            if (strcasecmp($oldDataAttributeValue, $newDataAttributeValue) !== 0) {
                                $changedAttributeCodes[] = $oldDataAttributeCode;
                            }
                        }
                    }

                    unset($newData[ $oldDataAttributeCode ]);
                }
            }
        }

        return array_values(array_unique($changedAttributeCodes));
    }
}
