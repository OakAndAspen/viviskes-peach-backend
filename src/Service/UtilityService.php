<?php

namespace App\Service;

use Firebase\JWT\JWT;

class UtilityService
{
    private $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Output the current percentage of an operation if it has changed
     *
     * @param $message
     * @param $section
     * @param $currentPercentage
     * @param $key
     * @param $total
     * @return float
     */
    public static function outputStatus($message, $section, $currentPercentage, $key, $total)
    {
        $newPercentage = round(($key + 1) * 100 / $total);
        if ($newPercentage != $currentPercentage) {
            $currentPercentage = $newPercentage;
            $section->overwrite($message . " (" . $currentPercentage . "%)");
        }
        return $currentPercentage;
    }

    /**
     * Normalizes a string (lowercase, removes special characters and spaces, and so on)
     * @param $str
     * @return mixed|string
     */
    public static function normalize($str)
    {
        $str = strtolower($str);

        // Characters to replace by an underscore
        $charsToReplace = array(' ', '-');
        $str = str_replace($charsToReplace, '_', $str);

        // Special letters
        $ls = array('ä', 'à', 'â', 'å', 'á');
        $str = str_replace($ls, 'a', $str);
        $ls = array('é', 'è', 'ë', 'ê');
        $str = str_replace($ls, 'e', $str);
        $ls = array('î', 'ï');
        $str = str_replace($ls, 'i', $str);
        $ls = array('ö', 'ô', 'ø');
        $str = str_replace($ls, 'o', $str);
        $ls = array('ù', 'û', 'ü', 'ú');
        $str = str_replace($ls, 'u', $str);
        $str = str_replace('æ', 'ae', $str);
        $str = str_replace('œ', 'oe', $str);
        $str = str_replace('ç', 'c', $str);
        $str = str_replace('ÿ', 'y', $str);

        // Characters to remove
        $str = preg_replace('/[^a-zA-Z\d\s_]/', '', $str);
        $str = preg_replace('/[_]{2,}/', '_', $str);

        return $str;
    }

    /**
     * Returns a date in the YYYY-MM-DD format
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    public static function dateToString(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }

    /**
     * Returns a datetime in the YYYY-MM-DD HH:II:SS format
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    public static function datetimeToString(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Logs the message in an output file, optionally erasing the current content
     *
     * @param $message
     * @param bool $erase
     */
    public function log($message, $erase = false)
    {
        $fileUrl = $this->rootDir . DIRECTORY_SEPARATOR . 'annexes' . DIRECTORY_SEPARATOR . 'log.txt';
        $currentContent = file_get_contents($fileUrl);
        $newContent = $erase ? $message : $currentContent . "\n" . $message;
        file_put_contents($fileUrl, $newContent);
    }


    public static function generateJWT($user)
    {
        $jti = base64_encode(password_hash(rand(0, 999999), PASSWORD_BCRYPT));
        $data = [
            'iat' => time(),                    // Issued at
            'jti' => $jti,                      // Json Token Id
            'iss' => 'mtg.oakandaspen.ch',      // Issuer
            'data' => [                         // Data related to the user
                'userId' => $user->getId()
            ]
        ];
        $secretKey = $_ENV['JWT_SECRET'];
        return JWT::encode($data, $secretKey, 'HS512');
    }
}