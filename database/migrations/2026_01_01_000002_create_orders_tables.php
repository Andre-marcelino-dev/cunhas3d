<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex.: C3D-2026-000123
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pendente')->index();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Dados de entrega
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('cpf', 14)->nullable();
            $table->string('cep', 9);
            $table->string('address');
            $table->string('number', 20);
            $table->string('complement')->nullable();
            $table->string('district');
            $table->string('city');
            $table->string('state', 2);
            $table->text('notes')->nullable();

            // Mercado Pago
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_payment_id')->nullable()->index();
            $table->string('payment_status', 30)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('tracking_code')->nullable();
            $table->boolean('stock_restored')->default(false);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Registro de cada notificação recebida do Mercado Pago (evita processar duas vezes)
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('mp_payment_id')->index();
            $table->string('status', 30);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['mp_payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
