<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScriptBackTestingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('script_back_testings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('symbol');
            $table->decimal('stop_loss', 10, 2);
            $table->date('buy_date');
            $table->decimal('buy_price', 10, 2);
            $table->date('sell_date')->nullable();
            $table->decimal('sell_price', 10, 2)->nullable();
            $table->text('indicators')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('script_back_testings');
    }
}
