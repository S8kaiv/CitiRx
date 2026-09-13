<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tos_domains', function (Blueprint $table) {
            $table->increments('domain_id');
            $table->unsignedInteger('domain_number')->unique();
            $table->string('domain_name', 150);
            $table->decimal('prc_weight_percentage', 4, 2);
        });

        DB::statement(
            'ALTER TABLE tos_domains ADD CONSTRAINT chk_domain_weight '
            . 'CHECK (prc_weight_percentage BETWEEN 0 AND 100)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tos_domains');
    }
};