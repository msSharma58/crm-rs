<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // facebook, whatsapp
            $table->boolean('is_active')->default(false);
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->json('settings')->nullable(); // page_id, phone_number_id, form mappings
            $table->string('webhook_verify_token')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'provider']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('event_id')->nullable()->index();
            $table->string('event_type')->nullable();
            $table->string('status')->default('received'); // received, processed, failed, ignored
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['provider', 'event_id']);
        });

        Schema::create('integration_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('whatsapp');
            $table->string('direction'); // inbound, outbound
            $table->string('channel')->default('whatsapp');
            $table->string('external_id')->nullable()->index();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('message_type')->default('text');
            $table->text('body')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('received'); // received, sent, delivered, read, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'lead_id', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('external_provider')->nullable()->after('source');
            $table->string('external_id')->nullable()->after('external_provider');
            $table->json('integration_meta')->nullable()->after('external_id');
            $table->index(['organization_id', 'external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'external_provider', 'external_id']);
            $table->dropColumn(['external_provider', 'external_id', 'integration_meta']);
        });

        Schema::dropIfExists('integration_messages');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('integration_settings');
    }
};
