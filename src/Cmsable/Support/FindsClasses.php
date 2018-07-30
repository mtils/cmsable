<?php
/**
 *  * Created by mtils on 30.07.18 at 11:36.
 **/

namespace Cmsable\Support;


trait FindsClasses
{
    /**
     * @var array
     */
    protected $_namespaces = [];

    /**
     * @var bool
     */
    protected $_namespacesBooted = false;

    /**
     * @param string $namespace
     */
    public function appendNamespace($namespace)
    {

        $this->_bootIfNotBooted();
        $namespace = trim($namespace,'\\');

        if (in_array($namespace, $this->_namespaces)) {
            return;
        }

        $this->_namespaces[] = $namespace;
    }

    /**
     * @param string $namespace
     */
    public function prependNamespace($namespace)
    {
        $this->_bootIfNotBooted();
        $namespace = trim($namespace,'\\');

        if (in_array($namespace, $this->_namespaces)) {
            return;
        }
        $this->_namespaces[] = $namespace;
    }

    /**
     * @param string $baseName
     *
     * @return string
     */
    protected function cleanClassName($baseName)
    {
        return $baseName;
    }

    /**
     * @param string $name
     * @param array  $delimiters
     *
     * @return string
     */
    protected function camelCase($name, array $delimiters=['-','_'])
    {
        $spaceSeparated = str_replace($delimiters,' ', $name);
        return str_replace(' ', '', ucwords($spaceSeparated));
    }

    /**
     * @param string $baseName
     *
     * @return string
     */
    protected function findClass($baseName)
    {
        $this->_bootIfNotBooted();

        $baseName = $this->baseClassName($baseName);

        foreach ($this->_namespaces as $namespace) {
            $className = $namespace . '\\' . $baseName;
            if (class_exists($className)) {
                return $className;
            }
        }
    }

    /**
     * @param string $baseName
     *
     * @return string
     */
    protected function baseClassName($baseName)
    {
        return ucfirst($this->cleanClassName($baseName));
    }

    protected function _bootIfNotBooted()
    {
        if ($this->_namespacesBooted) {
            return;
        }

        $this->_namespacesBooted = true;

        if (property_exists($this, 'namespaces')) {
            $this->_namespaces = array_merge(
                $this->_namespaces,
                $this->namespaces
            );
        }
    }
}