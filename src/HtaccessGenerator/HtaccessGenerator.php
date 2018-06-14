<?php

namespace HtaccessGenerator;

abstract class HtaccessGenerator {
    /**
     * Generates hash for apache password file.
     *
     * @param string $password
     * @return string Hashed password
     */
    private static function generateApacheHash($password)
    {
        $ret = self::crypt_apr1_md5($password);
        return $ret;
    }

    /**
     * Creates a directory tree. Default permissions are 0777.
     *
     * Examples:
     *      createDirectory("a/b/c/"); // creates a/b/c
     *
     * @param string $path Path to create
     * @param int $permissions Permissions in UNIX octal format.
     * @return bool true on success, false on failure
     */
    private static function createDirectory($path, $permissions = 0777)
    {
        if (!file_exists($path)) {
            return mkdir($path, $permissions, true);
        } else {
            return false;
        }
    }

    const GENERATED_STORAGE_DIRECTORY = __DIR__ . "/../../generated";

    /**
     * Regenerates access and password files in ./generated/
     *
     * @param array(user => password) $credentials
     * @param string $directory for what directory to generate
     * @return bool
     * @throws \Exception
     */
    public static function generateApacheFiles($credentials, $directory = __DIR__ . '/../../')
    {
        if (!file_exists(self::GENERATED_STORAGE_DIRECTORY)) {
            if (! self::createDirectory(self::GENERATED_STORAGE_DIRECTORY)) {
                throw new \Exception('Failed to create directory ' . $directory);
            }
        }
        $passwordFileLocation = self::getRealPath($directory . '/.htpasswd');
        $accessFileLocation = self::getRealPath($directory . '/.htaccess');

        $accessFileContents = @file_get_contents($accessFileLocation);
        if (! $accessFileContents) $accessFileContents = '';

        $accessFileContents .=
            "\r\n" .
            "AuthType Basic\r\n" .
            "AuthName \"Restricted domain\" \r\n" .
            "AuthUserFile $passwordFileLocation \r\n" .
            "Require valid-user \r\n";

        $passwordFileContents = "";
        foreach ($credentials as $user => $password) {
            $encryptedPassword = self::generateApacheHash($password);
            $passwordFileContents .=
                "$user:$encryptedPassword \r\n";
        }

        return file_put_contents(self::GENERATED_STORAGE_DIRECTORY . '/.htaccess', $accessFileContents)
            && file_put_contents(self::GENERATED_STORAGE_DIRECTORY . '/.htpasswd', $passwordFileContents);

    }

    /**
     * Basic implementation of apr1 md5 hashing algorithm to produce apache-like password strings
     *
     * Don't roll your own crypto, kids
     *
     * @param string $plainTextPassword
     * @return string
     */
    private static function crypt_apr1_md5($plainTextPassword)
    {
        $salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
        $len = strlen($plainTextPassword);
        $text = $plainTextPassword . '$apr1$' . $salt;
        $bin = pack("H32", md5($plainTextPassword . $salt . $plainTextPassword));
        $tmp = "";
        for ($i = $len; $i > 0; $i -= 16) {
            $text .= substr($bin, 0, min(16, $i));
        }
        for ($i = $len; $i > 0; $i >>= 1) {
            $text .= ($i & 1) ? chr(0) : $plainTextPassword{0};
        }
        $bin = pack("H32", md5($text));
        for ($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plainTextPassword : $bin;
            if ($i % 3) $new .= $salt;
            if ($i % 7) $new .= $plainTextPassword;
            $new .= ($i & 1) ? $bin : $plainTextPassword;
            $bin = pack("H32", md5($new));
        }
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) $j = 5;
            $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
        }
        $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
        $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
            "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");

        return "$" . "apr1" . "$" . $salt . "$" . $tmp;
    }

    /**
     * As realpath() is unable to resolve non-existent files, this function does so
     *
     * Credits to Sven Arduwie (2008)
     *
     * @param string $path any path
     * @return string A real path to file
     */
    private static function getRealPath($path)
    {
        if (file_exists($path)) return realpath($path);
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $ret = implode(DIRECTORY_SEPARATOR, $absolutes);
        if (DIRECTORY_SEPARATOR === "/") {
            $ret = "/" . $ret;
        }
        return $ret;
    }
}