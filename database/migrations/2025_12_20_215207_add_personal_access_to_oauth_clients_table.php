<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('oauth_clients', function (Blueprint $table) {
        $table->boolean('personal_access_client')->default(false);
        $table->boolean('password_client')->default(false);
    });
}

public function down()
{
    Schema::table('oauth_clients', function (Blueprint $table) {
        $table->dropColumn(['personal_access_client', 'password_client']);
    });
}

};
