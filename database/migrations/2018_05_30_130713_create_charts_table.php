<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('period')->nullable();
            $table->string('track_spotify_id');
            $table->string('track_name');
            $table->string('track_artist');
            $table->text('track_data');
            $table->integer('position')->nullable();
            $table->integer('last_position')->nullable();
            $table->integer('periods_on_chart')->nullable();
            $table->integer('peak_position')->nullable();
            $table->tinyInteger('is_reentry')->nullable();
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
        Schema::dropIfExists('charts');
    }
}
