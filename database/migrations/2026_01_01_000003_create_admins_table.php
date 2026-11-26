<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa os administradores dos clientes:
 *  - admins -> quem acessa o painel /admin
 *  - users  -> somente clientes da loja
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Move os administradores que já existiam na tabela users
        $admins = DB::table('users')->where('is_admin', true)->get();

        foreach ($admins as $admin) {
            DB::table('admins')->insert([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => $admin->password,
                'created_at' => $admin->created_at,
                'updated_at' => now(),
            ]);
        }

        // Remove da tabela de clientes (só os que não têm pedidos, para não perder vendas)
        DB::table('users')
            ->where('is_admin', true)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.user_id', 'users.id'))
            ->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('cpf');
        });

        foreach (DB::table('admins')->get() as $admin) {
            DB::table('users')->updateOrInsert(
                ['email' => $admin->email],
                ['name' => $admin->name, 'password' => $admin->password, 'is_admin' => true,
                    'created_at' => $admin->created_at, 'updated_at' => now()]
            );
        }

        Schema::dropIfExists('admins');
    }
};
