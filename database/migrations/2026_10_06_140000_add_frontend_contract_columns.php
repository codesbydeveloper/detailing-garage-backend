<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('gst_number', 30)->nullable()->after('email');
        });

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->string('follow_up_time', 8)->nullable()->after('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('gst_number');
        });

        Schema::table('lead_followups', function (Blueprint $table) {
            $table->dropColumn('follow_up_time');
        });
    }
};
