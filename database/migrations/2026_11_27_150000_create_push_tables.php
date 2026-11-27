<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notificações no app (Web Push):
 *  - push_subscriptions -> cada celular/navegador que aceitou receber avisos (com ou sem login)
 *  - push_messages      -> histórico das novidades enviadas pelo admin
 *  - products.push_announced_at -> marca o produto que já foi anunciado como "novidade" (avisa uma vez só)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('endpoint_hash', 64)->unique(); // sha256 do endpoint (o endpoint é longo demais para índice)
            $table->text('endpoint');
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->string('body', 240);
            $table->string('url')->nullable();
            $table->string('kind', 20)->default('novidade'); // novidade | produto
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('push_announced_at')->nullable()->after('active');
        });

        // Produtos que já existem não são "novidade": marca todos para não anunciar ao editar
        DB::table('products')->update(['push_announced_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('push_announced_at'));
        Schema::dropIfExists('push_messages');
        Schema::dropIfExists('push_subscriptions');
    }
};
