<?php namespace Cmsable\View\Blade;

use Illuminate\View\Compilers\BladeCompiler as Compiler;

class ResourceDirectives51
{

    public $names = [
        'form'       => 'form',
        'searchForm' => 'searchForm'
    ];

    public function register(Compiler $blade)
    {
        foreach ($this->names as $method=>$name) {
            $this->{"register$method"}($blade, $name);
        }
    }

    protected function registerForm(Compiler $blade, $name)
    {

        $blade->directive($name, function($expression){

            $code = <<<'EOD'
                    <?php
                    if (isset($form)) {
                        echo $form;
                    } else{
                        $passed = array$expression$;
                        if (count($passed) == 2) {
                            $m = $passed[0];
                            $res = $passed[1];
                        }
                        if (count($passed) == 1) {
                            $m = $passed[0];
                            $res = isset($resource) ? $resource : null;
                        }
                        if (count($passed) == 0) {
                            $m = isset($model) ? $model : null;
                            $res = isset($resource) ? $resource : null;
                        }
                        echo Resource::form($m, $res);
                    }
                ?>
EOD;
            return str_replace('$expression$', $expression, $code);
        });

    }

    protected function registerSearchForm(Compiler $blade, $name)
    {

        $blade->directive($name, function($expression){

            $code = <<<'EOD'
                    <?php
                    if (isset($searchForm)) {
                        echo $searchForm;
                    } else{
                        $passed = array$expression$;
                        if (count($passed) == 2) {
                            $m = $passed[0];
                            $res = $passed[1];
                        }
                        if (count($passed) == 1) {
                            $m = $passed[0];
                            $res = isset($resource) ? $resource : null;
                        }
                        if (count($passed) == 0) {
                            $m = isset($model) ? $model : null;
                            $res = isset($resource) ? $resource : null;
                        }
                        echo Resource::searchForm($m, $res);
                    }
                ?>
EOD;
            return str_replace('$expression$', $expression, $code);
        });

    }

} 
