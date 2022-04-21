<?php

namespace Tofex\Help;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Strings
{
    /**
     * Generates with the help ov the  method generateUUID a GUID Version 5.
     * A GUID has the format %08s-%04s-%04x-%04x-%12s e.g. :
     * 32c8 f9ff-4352-545e-964c-7d5167e396ba .
     *
     * @return string
     */
    public function generateGUID5(): string
    {
        $hash = $this->generateUUID();

        return sprintf('%08s-%04s-%04x-%04x-%12s', // 32 bits for "time_low"
            substr($hash, 0, 8), // 16 bits for "time_mid"
            substr($hash, 8, 4), // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000, // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000, // 48 bits for "node"
            substr($hash, 20, 12));
    }

    /**
     * Generate a 40 characters long uuid. The uuid is a sha1 hash over a
     * string build with the magento base url, the micro time and a 7 digit
     * long random number e.g. : 216908463793cd292cad4756525ed23dafcf7af0 .
     *
     * @param string $namespace
     *
     * @return string a 40 character long hex value
     */
    public function generateUUID(string $namespace = 'foo'): string
    {
        $pid = getmypid();
        $time = ( string )microtime(true);
        $rand = ( string )mt_rand(1000000, 9999999);

        return sha1($namespace . '|' . $pid . '|' . $time . '|' . $rand);
    }

    /**
     * Clean non UTF-8 characters
     *
     * @param string $string
     *
     * @return string
     */
    public function cleanString(string $string): string
    {
        return mb_convert_encoding($string, 'UTF-8');
    }

    /**
     * Retrieve string length using UTF-8 charset
     *
     * @param string $string
     *
     * @return int
     */
    public function strlen(string $string): int
    {
        return mb_strlen($string, 'UTF-8');
    }

    /**
     * @param string $string
     * @param int    $maxLength
     *
     * @return string
     */
    public function cutString(string $string, int $maxLength): string
    {
        return strlen($string) > $maxLength ?
            (substr($string, 0, strpos(wordwrap($string, $maxLength - 3), "\n")) . '...') : $string;
    }
}
