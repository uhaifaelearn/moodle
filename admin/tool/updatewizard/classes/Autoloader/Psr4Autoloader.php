<?php

namespace tool_updatewizard\Autoloader;

/**
 * Class Psr4Autoloader
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md#class-example
 *
 * @package tool_updatewizard\Autoloader
 */
class Psr4Autoloader
{
    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = [];

    /**
     * @var array
     */
    protected $classMap = [];

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string    $prefix     The namespace prefix.
     * @param string    $baseDir    A base directory for class files in the namespace.
     * @param bool      $prepend    If true, prepend the base directory to the stack instead of appending it;
     *                                  this causes it to be searched first rather than last.
     *
     * @return void
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        // normalize namespace prefix
        $prefix     = trim($prefix, '\\') . '\\';

        // normalize the base directory with a trailing separator
        $baseDir    = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if (!array_key_exists($prefix, $this->prefixes)) {
            $this->prefixes[$prefix] = array();
        }

        // retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * @param array $classMap Class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }
    
    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on failure.
     */
    public function loadClass($class)
    {
        if (array_key_exists($class, $this->classMap) && $file = $this->requireFile($this->classMap[$class])) {
                // yes, we're done
                return $file;
        }

        // the current namespace prefix
        $prefix = $class;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {
            // retain the trailing namespace separator in the prefix
            $prefix         = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relativeClass  = substr($class, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mappedFile     = $this->loadMappedFile($prefix, $relativeClass);

            if (false !== $mappedFile) {
                return $mappedFile;
            }

            // remove the trailing namespace separator for the next iteration of strrpos()
            $prefix         = rtrim($prefix, '\\');
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix        The namespace prefix.
     * @param string $relativeClass The relative class name.
     *
     * @return mixed    Boolean false if no mapped file can be loaded,
     *                      or the name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        // are there any base directories for this namespace prefix?
        if (!array_key_exists($prefix, $this->prefixes)) {
            return false;
        }

        $relativeClass = str_replace('\\', '/', $relativeClass);

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $baseDir) {
            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $baseDir.$relativeClass.'.php';

            // if the mapped file exists, require it
            if ($this->requireFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        require $file;

        return true;
    }
}
