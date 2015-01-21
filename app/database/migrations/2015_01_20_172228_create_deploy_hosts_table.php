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
            $table->string('site_id');
            $table->string('deploy_id');
            $table->string('job_id');
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
