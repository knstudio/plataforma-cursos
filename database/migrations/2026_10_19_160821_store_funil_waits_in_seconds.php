<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Funnel waits in seconds instead of minutes, so a message can follow the
 * previous one a few seconds later.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('funil_blocos', function (Blueprint $table) {
            $table->renameColumn('espera_inicio_min', 'espera_inicio_seg');
            $table->renameColumn('espera_se_comprou_min', 'espera_se_comprou_seg');
        });

        Schema::table('funil_mensagens', function (Blueprint $table) {
            $table->renameColumn('espera_min', 'espera_seg');
        });

        DB::table('funil_blocos')->update([
            'espera_inicio_seg' => DB::raw('espera_inicio_seg * 60'),
            'espera_se_comprou_seg' => DB::raw('espera_se_comprou_seg * 60'),
        ]);
        DB::table('funil_mensagens')->update(['espera_seg' => DB::raw('espera_seg * 60')]);
    }

    /**
     * Reverse the migrations (waits shorter than a minute become whole minutes).
     */
    public function down(): void
    {
        DB::table('funil_blocos')->update([
            'espera_inicio_seg' => DB::raw('(espera_inicio_seg + 59) / 60'),
            'espera_se_comprou_seg' => DB::raw('(espera_se_comprou_seg + 59) / 60'),
        ]);
        DB::table('funil_mensagens')->update(['espera_seg' => DB::raw('(espera_seg + 59) / 60')]);

        Schema::table('funil_blocos', function (Blueprint $table) {
            $table->renameColumn('espera_inicio_seg', 'espera_inicio_min');
            $table->renameColumn('espera_se_comprou_seg', 'espera_se_comprou_min');
        });

        Schema::table('funil_mensagens', function (Blueprint $table) {
            $table->renameColumn('espera_seg', 'espera_min');
        });
    }
};
