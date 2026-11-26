<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador padrão (troque a senha depois do primeiro acesso!)
        // Fica na tabela "admins", separado dos clientes
        Admin::firstOrCreate(['email' => 'admin@cunhas3d.com.br'], [
            'name' => 'Administrador',
            'password' => 'admin123',
        ]);

        // Cliente de teste (tabela "users")
        User::firstOrCreate(['email' => 'cliente@teste.com'], [
            'name' => 'Cliente Teste',
            'password' => 'cliente123',
            'phone' => '(11) 99999-0000',
            'cep' => '01310-100',
            'address' => 'Avenida Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $catalog = [
            ['Decoração', '#a78bfa', 'Vasos, esculturas e peças que transformam ambientes.', [
                ['Vaso Paramétrico Onda', 'vase', '#a78bfa', 'PLA', 89.90, 74.90, true, 'Vaso com parede ondulada gerada por algoritmo, impresso em espiral contínua.'],
                ['Escultura Nó Infinito', 'torusknot', '#f472b6', 'PLA', 129.90, null, true, 'Escultura de mesa inspirada em topologia matemática.'],
                ['Cachepô Geométrico Low-Poly', 'icosahedron', '#34d399', 'PETG', 59.90, null, false, 'Cachepô facetado para suculentas, com furo de drenagem.'],
            ]],
            ['Luminárias', '#fbbf24', 'Luminárias com padrões que só a impressão 3D consegue criar.', [
                ['Luminária Voronoi', 'lamp', '#fbbf24', 'PLA', 199.90, 169.90, true, 'Cúpula com malha Voronoi que projeta sombras orgânicas. Acompanha soquete E27.'],
                ['Abajur Espiral', 'vase', '#fde68a', 'PETG', 159.90, null, false, 'Abajur translúcido impresso em modo vaso, luz suave e difusa.'],
            ]],
            ['Miniaturas & RPG', '#22d3ee', 'Miniaturas em resina com nível de detalhe altíssimo.', [
                ['Guerreiro Rúnico 32mm', 'figure', '#94a3b8', 'Resina', 49.90, null, true, 'Miniatura para RPG de mesa em resina 8K, pronta para pintura.'],
                ['Dado D20 Cristal Gigante', 'icosahedron', '#22d3ee', 'Resina', 39.90, 29.90, false, 'D20 de 5 cm em resina translúcida.'],
                ['Kit Terreno Masmorra', 'gear', '#78716c', 'PLA', 149.90, null, false, 'Peças modulares de terreno para montar suas masmorras.'],
            ]],
            ['Peças Técnicas', '#38bdf8', 'Peças funcionais, protótipos e reposição sob medida.', [
                ['Engrenagem Helicoidal Nylon', 'gear', '#e2e8f0', 'Nylon', 34.90, null, false, 'Engrenagem resistente ao desgaste para mecanismos e reposição.'],
                ['Suporte Articulado para Celular', 'torusknot', '#38bdf8', 'PETG', 44.90, 39.90, true, 'Suporte de mesa com articulação e ajuste de ângulo.'],
                ['Organizador de Cabos Flex', 'gear', '#f97316', 'TPU', 24.90, null, false, 'Clipes flexíveis em TPU para organizar cabos na mesa.'],
            ]],
            ['Utilidades', '#4ade80', 'Objetos úteis para o dia a dia, casa e escritório.', [
                ['Porta-Canetas Hexagonal', 'icosahedron', '#4ade80', 'PLA', 29.90, null, false, 'Organizador de mesa modular em colmeia.'],
                ['Chaveiro Personalizado', 'torusknot', '#e879f9', 'PLA', 14.90, null, false, 'Chaveiro com o nome ou logo que você quiser.'],
            ]],
        ];

        foreach ($catalog as $position => [$name, $color, $description, $products]) {
            $category = Category::updateOrCreate(
                ['name' => $name],
                ['color' => $color, 'description' => $description, 'position' => $position, 'active' => true]
            );

            foreach ($products as [$pName, $shape, $pColor, $material, $price, $sale, $featured, $short]) {
                Product::firstOrCreate(['name' => $pName], [
                    'category_id' => $category->id,
                    'short_description' => $short,
                    'description' => $short."\n\nImpresso sob demanda no nosso estúdio com controle de qualidade em cada camada. ".
                        "Acabamento revisado à mão, embalagem protegida e envio para todo o Brasil.\n\n".
                        "• Material: {$material}\n• Altura de camada: 0,12 mm a 0,2 mm\n• Cores sob consulta",
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock' => random_int(5, 40),
                    'material' => $material,
                    'color' => $pColor,
                    'shape' => $shape,
                    'weight_grams' => random_int(50, 600),
                    'production_days' => random_int(1, 5),
                    'featured' => $featured,
                    'active' => true,
                ]);
            }
        }
    }
}
