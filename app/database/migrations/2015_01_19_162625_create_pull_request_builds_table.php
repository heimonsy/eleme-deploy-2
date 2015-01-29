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
            $table->integer('job_id')->default(0);
            $table->integer('pull_request_id');
            $table->string('title')->default('');
            $table->integer('number')->default(0);
            $table->string('commit')->default('');
            $table->string('repo_name')->default('');
            $table->string('user_login')->default('');
            $table->string('status')->default('');
            $table->string('build_status')->default('');
            $table->string('test_status')->default('');
            $table->string('merged_by')->default('');
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
