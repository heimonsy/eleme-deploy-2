<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HostTypeCatalogAddIsSendNotify extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('host_type_catalogs', function (Blueprint $table) {
            $table->integer('is_send_notify')->default(0)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('host_type_catalogs', function (Blueprint $table) {
            $table->dropColumn('is_send_notify');
        });
    }

}
