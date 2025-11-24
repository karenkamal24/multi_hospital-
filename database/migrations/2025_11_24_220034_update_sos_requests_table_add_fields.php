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
        Schema::table('sos_requests', function (Blueprint $table) {
            // Change status enum from open/closed to active/pending/completed/cancelled
            $table->dropColumn('status');
        });

        Schema::table('sos_requests', function (Blueprint $table) {
            $table->enum('status', ['active', 'pending', 'completed', 'cancelled'])->default('active')->after('description');
            $table->foreignId('accepted_donor_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            $table->foreignId('hospital_id')->nullable()->after('accepted_donor_id')->constrained('hospitals')->onDelete('set null');
            $table->enum('operation_status', ['pending', 'completed', 'cancelled'])->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropForeign(['accepted_donor_id']);
            $table->dropForeign(['hospital_id']);
            $table->dropColumn(['accepted_donor_id', 'hospital_id', 'operation_status', 'status']);
        });

        Schema::table('sos_requests', function (Blueprint $table) {
            $table->enum('status', ['open', 'closed'])->default('open')->after('description');
        });
    }
};
