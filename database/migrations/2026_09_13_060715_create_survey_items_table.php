<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_items', function (Blueprint $table) {
            $table->uuid('item_id')->primary();
            $table->enum('iso_characteristic', [
                'functional_suitability',
                'performance_efficiency',
                'compatibility',
                'usability',
                'reliability',
                'security',
                'maintainability',
                'portability',
                'interaction_capability',
                'flexibility',
                'safety'
            ]);
            $table->string('item_code', 50)->unique();
            $table->text('item_text');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_items');
    }
};