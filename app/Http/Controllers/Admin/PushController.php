<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PushMessage;
use App\Models\PushSubscription;
use App\Services\PushService;
use Illuminate\Http\Request;

/** Painel: escrever e mandar novidades/promoções para todos que têm o app */
class PushController extends Controller
{
    public function index(PushService $push)
    {
        return view('admin.push', [
            'configured' => $push->configured(),
            'devices' => PushSubscription::count(),
            'customers' => PushSubscription::whereNotNull('user_id')->distinct()->count('user_id'),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'history' => PushMessage::latest()->take(20)->get(),
        ]);
    }

    public function store(Request $request, PushService $push)
    {
        $data = $request->validate([
            'title' => 'required|string|max:60',
            'body' => 'required|string|max:200',
            'product_id' => 'nullable|integer|exists:products,id',
        ], [], ['title' => 'título', 'body' => 'mensagem', 'product_id' => 'produto']);

        if (! $push->configured()) {
            return back()->withInput()->with('error', 'As notificações ainda não estão configuradas no .env (VAPID_...).');
        }

        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;
        $url = $product ? '/produto/'.$product->slug : '/home';

        [$sent, $failed] = $push->toAll([
            'title' => $data['title'],
            'body' => $data['body'],
            'url' => $url,
            'image' => $product?->image_url,
            'tag' => 'novidade-'.now()->timestamp,
        ]);

        PushMessage::create([
            'title' => $data['title'], 'body' => $data['body'], 'url' => $url,
            'kind' => 'novidade', 'sent' => $sent, 'failed' => $failed,
        ]);

        return redirect()->route('admin.push.index')->with('success', $sent
            ? "Notificação enviada para {$sent} ".($sent === 1 ? 'aparelho' : 'aparelhos').'!'
            : 'Ninguém recebeu: ainda não há aparelhos com notificações ativadas.');
    }
}
