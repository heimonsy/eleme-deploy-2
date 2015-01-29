<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeployHostsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deploy_hosts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->integer('deploy_id');
            $table->integer('host_type_id');
            $table->integer('job_id');
            $table->integer('task_id')->default(0);
            $table->string('type');
            $table->string('host_ip');
            $table->string('host_name');
            $table->string('host_port');
            $table->string('status');
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
        Schema::drop('deploy_hosts');
    }

}
