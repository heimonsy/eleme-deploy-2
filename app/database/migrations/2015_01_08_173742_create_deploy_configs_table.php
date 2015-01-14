<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeployConfigsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deploy_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->string('remote_user');
            $table->string('remote_owner');
            $table->string('remote_static_dir');
            $table->string('remote_app_dir');
            $table->string('app_script');
            $table->string('static_script');
            $table->text('deploy_key');
            $table->string('deploy_key_passphrase');
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
        Schema::drop('deploy_configs');
    }
}
