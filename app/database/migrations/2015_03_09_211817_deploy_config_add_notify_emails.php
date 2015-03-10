<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeployConfigAddNotifyEmails extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deploy_configs', function (Blueprint $table) {
            $table->string('notify_emails')->default('')->after('static_script');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deploy_configs', function (Blueprint $table) {
            $table->dropColumn('notify_emails');
        });
    }

}
