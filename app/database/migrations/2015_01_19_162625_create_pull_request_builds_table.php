<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePullRequestBuildsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pull_request_builds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->integer('job_id');
            $table->integer('pull_request_id');
            $table->string('title');
            $table->integer('number');
            $table->string('commit');
            $table->string('repo_name');
            $table->string('user_login');
            $table->string('status');
            $table->string('build_status');
            $table->string('test_status');
            $table->string('merged_by');
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
        Schema::drop('pull_request_builds');
    }

}
