<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

class ManualMigrator{

    protected $con;

    public function __construct(Connection $con){
        $this->con = $con;
    }

    public function run(){

        $builder  = $this->con->getSchemaBuilder();

        $builder->create('pagetype_configs', function(Blueprint $table){

            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('page_type_id', 255);
            $table->string('varname', 255);
            $table->integer('page_id')->nullable();
            $table->integer('int_val')->nullable();
            $table->float('float_val')->nullable();
            $table->text('string_val')->nullable();

            $table->index('page_type_id');
            $table->index('varname');
            $table->index('page_id');

        });

    }

}