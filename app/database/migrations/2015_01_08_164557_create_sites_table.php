<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSitesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->string('repo_git')->default('');
            $table->string('static_dir')->default('');
            $table->string('rsync_exclude_file')->default('');
            $table->string('default_branch')->default('');
            $table->string('build_command')->default('');
            $table->string('test_command')->default('');
            $table->text('pull_key');
            $table->string('pull_key_passphrase')->default('');
            $table->string('hipchat_room')->default('');
            $table->string('hipchat_token')->default('');
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
        Schema::drop('sites');
    }
}
