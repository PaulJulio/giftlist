<?php
namespace util;
// global loader - the class_exists call seems to be a Mac-only issue
if (!class_exists('\\Loader',false)) {
    require_once(realpath(__DIR__) . '/../loader.php');
}
// namespace loader
final class Loader {
    // all files are expected to have a namespace that matches their containing directory
    // or be in the root directory (one above this one)
    // All file and directory names are lower case, while the actual classes may be mixed case
    public static function loadWithNameSpace($class) {
        $root = realpath(__DIR__ . '/../');
        $chunks = explode('\\', $class);
        $fn = strtolower($root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $chunks) . '.php');
        if (file_exists($fn)) {
            include_once($fn);
        }
    }
}
spl_autoload_register('\\' . __NAMESPACE__ .'\\Loader::loadWithNameSpace');
