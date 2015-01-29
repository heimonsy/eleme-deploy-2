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
            $table->integer('user_id');
            $table->integer('site_id');
            $table->integer('job_id')->default(0);
            $table->string('status');
            $table->string('type')->default('deploy');;
            $table->string('deploy_kind')->default('');
            $table->string('deploy_to')->default('');
            $table->string('description')->default('');
            $table->string('total_hosts')->default('');
            $table->string('success_hosts')->default(0);
            $table->string('error_hosts')->default(0);
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
