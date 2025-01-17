<?php
/**
 * The Phile Utility class
 */
namespace Phile\Core;

use Phile\Core\Container;

/**
 * Utility class
 *
 * @author  PhileCMS
 * @link    https://philecms.github.io
 * @license http://opensource.org/licenses/MIT
 * @package Phile
 */
class Utility
{

    /**
     * method to get the current http protocol
     *
     * @return     string the current protocol
     * @deprecated since 1.5 will be removed
     */
    public static function getProtocol()
    {
        return Container::getInstance()->get('Phile_Router')->getProtocol();
    }

    /**
     * detect base url
     *
     * @return     string
     * @deprecated since 1.5 will be removed
     */
    public static function getBaseUrl()
    {
        $container = Container::getInstance();
        if ($container->has('Phile_Router')) {
            $router = $container->get('Phile_Router');
        } else {
            // BC: some old 1.x plugins may call this before the core is initialized
            $router = new Router;
        }
        return $router->getBaseUrl();
    }

    /**
     * detect install path
     *
     * @return     string
     * @deprecated since 1.5 will be removed
     */
    public static function getInstallPath()
    {
        $path = self::getBaseUrl();
        $path = substr($path, strpos($path, '://') + 3);
        $path = substr($path, strpos($path, '/') + 1);

        return $path;
    }

    /**
     * resolve a file path by replace the mod: prefix
     *
     * @param string $path
     *
     * @return string|null the full filepath or null if file does not exists
     */
    public static function resolveFilePath($path)
    {
        // resolve MOD: prefix
        if (strtoupper(substr($path, 0, 3)) === 'MOD') {
            $path = str_ireplace('mod:', PLUGINS_DIR, $path);
        }
        // check if file exists
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * load files e.g. config files
     *
     * @param string $file
     *
     * @return mixed|null
     */
    public static function load($file)
    {
        if (file_exists($file)) {
            return include $file;
        }

        return null;
    }

    /**
     * check if a plugin is loaded
     *
     * @param      string $plugin
     * @return     bool
     * @deprecated since 1.5 will be removed
     * @use        'plugins_loaded' event
     */
    public static function isPluginLoaded($plugin)
    {
        $config = Container::getInstance()->get('Phile_Config');
        if ($config->get('plugins')) {
            return false;
        }
        $plugins = $config->get('plugins');
        return (isset($plugins[$plugin]['active']) && $plugins[$plugin]['active'] === true);
    }

    /**
     * static method to get files by directory and file filter
     *
     * @param string $directory
     * @param string $filter
     *
     * @return array
     */
    public static function getFiles($directory, $filter = '\Phile\FilterIterator\GeneralFileFilterIterator')
    {
        $files = new $filter(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
                )
            )
        );
        $result = array();
        foreach ($files as $file) {
            /**
             * @var \SplFileInfo $file
             */
            $result[] = $file->getPathname();
        }

        return $result;
    }

    /**
     * redirect to an url
     *
     * @param      string $url        the url to redirect to
     * @param      int                               $statusCode the http status code
     * @deprecated since 1.5 will be removed
     */
    public static function redirect($url, $statusCode = 302)
    {
        (new Response)->redirect($url, $statusCode);
    }

    /**
     * generate secure md5 hash
     *
     * @param string $value
     *
     * @return string
     */
    public static function getSecureMD5Hash($value)
    {
        $config = Container::getInstance()->get('Phile_Config');

        return md5($config->get('encryptionKey') . $value);
    }

    /**
     * method to generate a secure token
     * code from http://stackoverflow.com/a/13733588/1372085
     * modified by Frank Nägler
     *
     * @param int  $length
     * @param bool $widthSpecialChars
     * @param null|string $additionalChars
     *
     * @return string
     */
    public static function generateSecureToken($length = 32, $widthSpecialChars = true, $additionalChars = null)
    {
        $token        = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        if ($widthSpecialChars) {
            $codeAlphabet .= "!/()=?[]|{}";
        }
        if ($additionalChars !== null) {
            $codeAlphabet .= $additionalChars;
        }
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[Utility::crypto_rand_secure(0, strlen($codeAlphabet))];
        }

        return $token;
    }

    /**
     * method to get a more secure random value
     * code from http://stackoverflow.com/a/13733588/1372085
     *
     * @param $min
     * @param $max
     *
     * @return mixed
     */
    // @codingStandardsIgnoreStart
    public static function crypto_rand_secure($min, $max)
    {
        // @codingStandardsIgnoreEnd
        $range = $max - $min;
        if ($range < 0) {
            return $min;
        } // not so random...
        $log    = log($range, 2);
        $bytes  = (int)($log / 8) + 1; // length in bytes
        $bits   = (int)$log + 1; // length in bits
        $filter = (int)(1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);

        return $min + $rnd;
    }
}
