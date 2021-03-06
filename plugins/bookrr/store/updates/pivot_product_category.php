<?php namespace Bookrr\Store\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateAeroparksProductPivot extends Migration
{
    public function up()
    {
        Schema::create('bookrr_product_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('product_id');
            $table->integer('category_id');
            $table->primary(['product_id','category_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('bookrr_product_pivot');
    }
}
