<?php namespace Cmsable\View;

use Illuminate\View\FileViewFinder;

/**
 * This FileViewFinder uses a fallback Directory to load files.
 * If you organize your application by resources (users.index,users.show)
 * and organize your templates accordingly (users/index.blade.php) there is
 * often no template difference between a news list and a pages list.
 * So on View::make('news.index') this finder will look for:
 *
 * 1. $firstViewPath/users/index.blade.php
 * 2. $firstViewPath/resource/index.blade.php
 * 
 * 3. $secondViewPath/users/index.blade.php
 * 4. $secondViewPath/resource/index.blade.php
 * ... and so on
 *
 * A few names are blacklisted (like layouts and partials), add more via
 * addToBlacklist()
 **/
class FallbackFileViewFinder extends FileViewFinder{

    /**
     * The fallback dir to look for templates
     * @var string
     **/
    protected $fallbackDir = 'resources';

    protected $blacklist = ['layouts','partials'];

    /**
     * The fallback directory (no complete path) to have a second look
     *
     * @return string
     **/
    public function getFallbackDir(){

        return $this->fallbackDir;

    }

    /**
     * Set the fallback directory (just a directory name, no path)
     *
     * @param string The directory to fallback to
     * @return self
     **/
    public function setFallbackDir($fallbackDir){

        $this->fallbackDir = $fallbackDir;

        return $this;

    }

    public function addToBlacklist($name){

        if(!in_array($name, $this->blacklist)){
            $this->blacklist[] = $name;
        }

        return $this;

    }

    public function removeFromBlackList($name){

        $this->blacklist = array_filter($this->blacklist, function($listed) use ($name){
            return ($listed != $name);
        });

    }

    /**
     * Another helper method. This method allows to add a location which will
     * loaded prior the first appended
     *
     * @param string The Location
     * @return static
     **/
    public function prependLocation($location){

        array_unshift($this->paths, $location);

        return $this;

    }

    /**
     * Get an array of possible view files.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {

        $names = $this->getPossibleViewNames($name);

        $viewFiles = [];

        array_walk($names, function($name) use (&$viewFiles)
        {
            foreach($this->extensions as $extension){

                $viewFiles[] = str_replace('.', '/', $name).'.'.$extension;

            }

        });

        return $viewFiles;
    }

    protected function getPossibleViewNames($name){

        if(!str_contains($name,'.')){

            return [$name];

        }

        list($head, $tail) = $this->getHeadAndTail($name);

        if(in_array($head, $this->blacklist)){

            return [$name];

        }

        // If a ! is prepended ignore fallbacks
        if(ends_with($name,'!')){
            return [substr($name,0,-1)];
        }

        return [
            implode('.',[$head, $tail]),
            implode('.',[$this->getFallbackDir(), $tail])
        ];

    }

    protected function getHeadAndTail($viewName){

        $tiles = explode('.',$viewName);

        $head = array_shift($tiles);

        return [$head, implode('.', $tiles)];

    }

    /**
     * Copy a FileViewFinder to a FallbackFileViewFinder
     *
     * @param \Illuminate\View\FileViewFinder $otherFinder
     * @return static
     **/
    public static function fromOther(FileViewFinder $otherFinder){

        $copy = new static($otherFinder->getFilesystem(),
                           $otherFinder->getPaths(),
                           $otherFinder->getExtensions());

        if($otherFinder instanceof FallbackFileViewFinder){
            $copy->setFallbackDir($otherFinder->getFallbackDir());
        }

        return $copy;

    }

}