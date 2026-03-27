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
        if (! Schema::hasTable('evaluasi')) {
            return;
        }

        Schema::table('evaluasi', function (Blueprint $table) {
            if (Schema::hasColumn('evaluasi', 'id_peserta')) {
                $table->dropForeign(['id_peserta']);
                $table->dropColumn('id_peserta');
            }

            $columnsToDrop = [];

            foreach (['nama_peserta', 'email_peserta', 'nip_peserta'] as $column) {
                if (Schema::hasColumn('evaluasi', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('evaluasi')) {
            return;
        }

        Schema::table('evaluasi', function (Blueprint $table) {
            if (! Schema::hasColumn('evaluasi', 'id_peserta')) {
                $table->foreignId('id_peserta')->nullable()->after('id_kegiatan');
                $table->foreign('id_peserta')->references('id_peserta')->on('peserta')->nullOnDelete();
            }

            if (! Schema::hasColumn('evaluasi', 'nama_peserta')) {
                $table->string('nama_peserta', 255)->default('Anonim')->after('id_peserta');
            }

            if (! Schema::hasColumn('evaluasi', 'email_peserta')) {
                $table->string('email_peserta', 255)->nullable()->after('nama_peserta');
            }

            if (! Schema::hasColumn('evaluasi', 'nip_peserta')) {
                $table->string('nip_peserta', 50)->nullable()->after('email_peserta');
            }
        });
    }
};
