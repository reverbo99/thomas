<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disbursements')) {
            Schema::create('disbursements', function (Blueprint $table) {
                $table->id();
                $table->date('batch_date')->unique();
                $table->string('reference', 64)->unique();
                $table->string('currency', 8)->default('TZS');
                // open | processing | completed | completed_with_errors
                $table->string('status', 32)->default('open')->index();
                $table->unsignedInteger('total_items')->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->unsignedInteger('paid_items')->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->unsignedInteger('failed_items')->default(0);
                $table->unsignedInteger('needs_details_items')->default(0);
                $table->timestamp('collected_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('disbursement_items')) {
            Schema::create('disbursement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('disbursement_id')->constrained('disbursements')->cascadeOnDelete();

                // booking_refund | excess_luggage | payout_transaction |
                // special_hire_withdrawal | special_hire_refund
                $table->string('source_type', 40);
                $table->unsignedBigInteger('source_id');
                $table->string('source_reference', 100)->nullable();
                $table->string('booking_code', 100)->nullable();

                // customer | vendor | bus_owner | special_hire_owner
                $table->string('beneficiary_type', 30)->default('customer');
                $table->unsignedBigInteger('beneficiary_user_id')->nullable();
                $table->string('beneficiary_name', 150)->nullable();

                // mobile_money | bank
                $table->string('payout_channel', 20)->default('mobile_money');
                $table->string('payout_number', 100)->nullable();
                $table->string('bank_name', 150)->nullable();
                $table->string('bank_bic', 30)->nullable();

                $table->decimal('amount', 15, 2)->default(0);
                $table->string('currency', 8)->default('TZS');

                // pending | processing | paid | failed | needs_details | skipped | reversed
                $table->string('status', 24)->default('pending')->index();
                // Unique ClickPesa order reference (alphanumeric only).
                $table->string('reference', 64)->unique();
                $table->string('gateway_id', 100)->nullable();
                $table->string('gateway_status', 40)->nullable();
                $table->decimal('gateway_fee', 15, 2)->nullable();
                $table->json('gateway_response')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('next_attempt_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();

                $table->unique(['source_type', 'source_id'], 'disbursement_source_unique');
                $table->index(['status', 'next_attempt_at'], 'disbursement_status_next_idx');
            });
        }

        if (Schema::hasTable('refund')) {
            Schema::table('refund', function (Blueprint $table) {
                if (! Schema::hasColumn('refund', 'payout_method')) {
                    $table->string('payout_method', 20)->nullable();
                }
                if (! Schema::hasColumn('refund', 'bank_name')) {
                    $table->string('bank_name', 150)->nullable();
                }
                if (! Schema::hasColumn('refund', 'bank_bic')) {
                    $table->string('bank_bic', 30)->nullable();
                }
                if (! Schema::hasColumn('refund', 'disbursed_at')) {
                    $table->timestamp('disbursed_at')->nullable();
                }
                if (! Schema::hasColumn('refund', 'disbursement_item_id')) {
                    $table->unsignedBigInteger('disbursement_item_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (! Schema::hasColumn('settings', 'disbursement_enabled')) {
                    $table->boolean('disbursement_enabled')->default(true);
                }
                if (! Schema::hasColumn('settings', 'disbursement_run_time')) {
                    $table->string('disbursement_run_time', 5)->default('00:00');
                }
                if (! Schema::hasColumn('settings', 'disbursement_live')) {
                    $table->boolean('disbursement_live')->default(false);
                }
                if (! Schema::hasColumn('settings', 'disbursement_last_run_at')) {
                    $table->timestamp('disbursement_last_run_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                foreach ([
                    'disbursement_enabled',
                    'disbursement_run_time',
                    'disbursement_live',
                    'disbursement_last_run_at',
                ] as $column) {
                    if (Schema::hasColumn('settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('refund')) {
            Schema::table('refund', function (Blueprint $table) {
                foreach ([
                    'payout_method',
                    'bank_name',
                    'bank_bic',
                    'disbursed_at',
                    'disbursement_item_id',
                ] as $column) {
                    if (Schema::hasColumn('refund', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('disbursement_items');
        Schema::dropIfExists('disbursements');
    }
};
