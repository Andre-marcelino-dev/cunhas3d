<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Endereços de entrega do cliente (Casa, Trabalho...) */
class EnderecoController extends Controller
{
    public function index(Request $request)
    {
        $enderecos = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->orderBy('label')->get();

        // Primeiro acesso: aproveita o endereço que o cliente já usou no site
        if ($enderecos->isEmpty() && $request->user()->address) {
            $u = $request->user();
            $enderecos = collect([Address::create([
                'user_id' => $u->id, 'label' => 'Casa', 'cep' => $u->cep, 'address' => $u->address,
                'number' => $u->number ?? 's/n', 'complement' => $u->complement, 'district' => $u->district ?? '',
                'city' => $u->city ?? '', 'state' => $u->state ?? '', 'is_default' => true,
            ])]);
        }

        return response()->json(['success' => true, 'data' => $enderecos->map(fn ($e) => Formata::endereco($e))]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $dados = $this->validar($request);

        $endereco = DB::transaction(function () use ($user, $dados) {
            $primeiro = ! Address::where('user_id', $user->id)->exists();

            return Address::create([...$dados, 'user_id' => $user->id, 'is_default' => $primeiro]);
        });

        return response()->json(['success' => true, 'message' => 'Endereço salvo!', 'data' => Formata::endereco($endereco)], 201);
    }

    public function update(Request $request, Address $endereco)
    {
        $this->doCliente($request, $endereco);
        $endereco->update($this->validar($request));

        return response()->json(['success' => true, 'message' => 'Endereço atualizado!', 'data' => Formata::endereco($endereco)]);
    }

    public function destroy(Request $request, Address $endereco)
    {
        $this->doCliente($request, $endereco);

        DB::transaction(function () use ($endereco) {
            $eraPrincipal = $endereco->is_default;
            $endereco->delete();

            if ($eraPrincipal) {
                Address::where('user_id', $endereco->user_id)->oldest()->first()?->update(['is_default' => true]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Endereço excluído.']);
    }

    /** Marca o endereço como o usado nas entregas */
    public function principal(Request $request, Address $endereco)
    {
        $this->doCliente($request, $endereco);

        DB::transaction(function () use ($endereco) {
            Address::where('user_id', $endereco->user_id)->update(['is_default' => false]);
            $endereco->update(['is_default' => true]);
        });

        return response()->json(['success' => true, 'message' => 'Endereço de entrega alterado.', 'data' => Formata::endereco($endereco)]);
    }

    private function doCliente(Request $request, Address $endereco): void
    {
        abort_unless($endereco->user_id === $request->user()->id, 404);
    }

    private function validar(Request $request): array
    {
        $d = $request->validate([
            'nome' => 'required|string|max:40',
            'cep' => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'endereco' => 'required|string|max:160',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'uf' => 'required|string|size:2',
        ], [], [
            'nome' => 'nome do endereço', 'endereco' => 'endereço', 'numero' => 'número',
            'bairro' => 'bairro', 'cidade' => 'cidade', 'uf' => 'UF',
        ]);

        $cep = preg_replace('/\D/', '', $d['cep']);

        return [
            'label' => $d['nome'],
            'cep' => substr($cep, 0, 5).'-'.substr($cep, 5),
            'address' => $d['endereco'],
            'number' => $d['numero'],
            'complement' => $d['complemento'] ?? null,
            'district' => $d['bairro'],
            'city' => $d['cidade'],
            'state' => strtoupper($d['uf']),
        ];
    }
}
