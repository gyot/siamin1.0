<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tpk')->nullable()->after('id_kegiatan');
            $table->foreign('id_tpk')->references('id_tpk')->on('tpk')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropForeign(['id_tpk']);
            $table->dropColumn('id_tpk');
        });
    }
};
