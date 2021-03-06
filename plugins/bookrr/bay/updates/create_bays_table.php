<?php namespace Bookrr\Bay\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBaysTable extends Migration
{
    public function up()
    {
        Schema::create('bookrr_bay', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('zone_id')->nullable();
            $table->string('name');
            $table->string('status',50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookrr_bay');
    }
}
