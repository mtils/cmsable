<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

if(!class_exists('Schema')){
    class_alias('','Schema');
}

class CreatePagetypeConfig extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('pagetype_configs', function(Blueprint $table){

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

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('pagetype_configs');
	}

}
