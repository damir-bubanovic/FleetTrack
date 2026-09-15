<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('type');
            $table->string('severity')->default('warning');

            $table->json('conditions');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'company_id',
                'type',
                'is_active',
            ]);

            $table->index([
                'company_id',
                'vehicle_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
