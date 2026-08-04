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
        Schema::create('drivers', function (Blueprint $table): void {

            $table->id();

            /*
             |--------------------------------------------------------------------------
             | Relationships
             |--------------------------------------------------------------------------
             */

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('fleet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Driver Information
             |--------------------------------------------------------------------------
             */

            $table->string('employee_number');

            $table->string('first_name');
            $table->string('last_name');

            $table->string('phone')
                ->nullable();

            $table->string('email')
                ->nullable();

            /*
             |--------------------------------------------------------------------------
             | Driving Licence
             |--------------------------------------------------------------------------
             */

            $table->string('license_number');

            $table->string('license_category');

            $table->date('license_expiry_date');

            /*
             |--------------------------------------------------------------------------
             | Employment
             |--------------------------------------------------------------------------
             */

            $table->date('employment_date');

            /*
             |--------------------------------------------------------------------------
             | Additional Information
             |--------------------------------------------------------------------------
             */

            $table->text('notes')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            /*
             |--------------------------------------------------------------------------
             | Unique Constraints
             |--------------------------------------------------------------------------
             */

            $table->unique([
                'company_id',
                'employee_number',
            ]);

            $table->unique([
                'company_id',
                'license_number',
            ]);

            $table->unique([
                'company_id',
                'email',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
