<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeploysTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deploys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->string('site_id');
            $table->string('job_id');
            $table->string('status');
            $table->string('type');
            $table->string('deploy_kind');
            $table->string('deploy_to');
            $table->string('description');
            $table->string('commit');
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
        Schema::drop('deploys');
    }
}
