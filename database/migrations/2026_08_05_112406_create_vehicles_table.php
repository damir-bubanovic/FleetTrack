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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('fleet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('registration_number');
            $table->string('vin')->unique();

            $table->string('manufacturer');
            $table->string('model');
            $table->unsignedSmallInteger('year');

            $table->string('color')->nullable();
            $table->string('fuel_type');
            $table->string('transmission');

            $table->unsignedBigInteger('odometer')->default(0);

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'company_id',
                'fleet_id',
            ]);

            $table->index([
                'company_id',
                'registration_number',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
