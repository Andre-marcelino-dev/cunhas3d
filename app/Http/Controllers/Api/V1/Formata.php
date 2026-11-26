<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;

/**
 * Monta o JSON que a API devolve para o app.
 * Imagens vão como caminho relativo ("/storage/..."): o app junta com o endereço do servidor,
 * assim funciona tanto no PC (localhost) quanto no celular (IP do PC).
 */
class Formata
{
    public static function caminho(?string $path): ?string
    {
        return $path ? '/storage/'.ltrim($path, '/') : null;
    }

    public static function categoria(Category $c): array
    {
        return [
            'id' => $c->id,
            'nome' => $c->name,
            'slug' => $c->slug,
            'descricao' => $c->description,
            'cor' => $c->color,
            'imagem' => self::caminho($c->image),
            'total_produtos' => $c->products_count ?? null,
        ];
    }

    public static function produto(Product $p, bool $completo = false): array
    {
        $dados = [
            'id' => $p->id,
            'slug' => $p->slug,
            'nome' => $p->name,
            'descricao_curta' => $p->short_description,
            'preco' => (float) $p->price,
            'preco_final' => $p->final_price,
            'desconto' => $p->discount_percent,
            'estoque' => (int) $p->stock,
            'material' => $p->material,
            'cor' => $p->color,
            'forma' => $p->shape,
            'imagem' => self::caminho($p->image),
            'destaque' => (bool) $p->featured,
            'categoria' => $p->relationLoaded('category') && $p->category ? [
                'id' => $p->category->id,
                'nome' => $p->category->name,
                'slug' => $p->category->slug,
                'cor' => $p->category->color,
            ] : null,
        ];

        if ($completo) {
            $dados += [
                'sku' => $p->sku,
                'descricao' => $p->description,
                'peso_gramas' => (int) $p->weight_grams,
                'prazo_producao' => (int) $p->production_days,
                'imagens' => $p->images->map(fn ($i) => self::caminho($i->path))->values(),
                // Produtos com variações: o app deve enviar variacao_id ao calcular a sacola e criar o pedido
                'variacoes' => $p->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'nome' => $v->name,
                    'preco' => (float) $v->price,
                    'preco_final' => $v->final_price,
                    'desconto' => $v->discount_percent,
                    'estoque' => (int) $v->stock,
                    'imagem' => self::caminho($v->image ?? $p->image),
                ])->values(),
            ];
        }

        return $dados;
    }

    public static function cliente(User $u): array
    {
        return [
            'id' => $u->id,
            'nome' => $u->name,
            'email' => $u->email,
            'telefone' => $u->phone,
            'cpf' => $u->cpf,
        ];
    }

    public static function endereco(Address $a): array
    {
        return [
            'id' => $a->id,
            'nome' => $a->label,
            'cep' => $a->cep,
            'endereco' => $a->address,
            'numero' => $a->number,
            'complemento' => $a->complement,
            'bairro' => $a->district,
            'cidade' => $a->city,
            'uf' => $a->state,
            'principal' => $a->is_default,
        ];
    }

    public static function pedido(Order $o, bool $completo = false): array
    {
        $dados = [
            'codigo' => $o->code,
            'status' => $o->status,
            'status_texto' => $o->status_label,
            'subtotal' => (float) $o->subtotal,
            'frete' => (float) $o->shipping,
            'total' => (float) $o->total,
            'pode_pagar' => $o->isPayable(),
            'criado_em' => $o->created_at?->toIso8601String(),
            'itens' => $o->items->map(fn ($i) => [
                'produto_id' => $i->product_id,
                'variacao_id' => $i->product_variant_id,
                'nome' => $i->name,
                'preco' => (float) $i->price,
                'quantidade' => (int) $i->quantity,
                'total' => (float) $i->total,
                'cor' => $i->product?->color,
                'forma' => $i->product?->shape,
                'imagem' => self::caminho($i->product?->image),
                'slug' => $i->product?->slug,
            ])->values(),
        ];

        if ($completo) {
            $dados += [
                'entrega' => [
                    'nome' => $o->name,
                    'telefone' => $o->phone,
                    'cep' => $o->cep,
                    'endereco' => $o->address,
                    'numero' => $o->number,
                    'complemento' => $o->complement,
                    'bairro' => $o->district,
                    'cidade' => $o->city,
                    'uf' => $o->state,
                ],
                'observacao' => $o->notes,
                'forma_pagamento' => $o->payment_method,
                'pago_em' => $o->paid_at?->toIso8601String(),
                'rastreio' => $o->tracking_code,
                'historico' => $o->histories->map(fn ($h) => [
                    'status' => $h->status,
                    'status_texto' => $h->status_label,
                    'nota' => $h->note,
                    'data' => $h->created_at?->toIso8601String(),
                ])->values(),
            ];
        }

        return $dados;
    }

    public static function depoimento(Testimonial $t): array
    {
        return [
            'id' => $t->id,
            'nota' => (int) $t->rating,
            'mensagem' => $t->message,
            'aprovado' => (bool) $t->approved,
            'autor' => $t->relationLoaded('user') ? str($t->user?->name)->before(' ')->toString() : null,
            'data' => $t->created_at?->toIso8601String(),
        ];
    }
}
