<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::with('product')->latest();

        if ($request->query('filtro') === 'nao-lidas') {
            $query->unread();
        }

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"));
        }

        return view('admin.messages.index', ['messages' => $query->paginate(20)->withQueryString()]);
    }

    public function show(ContactMessage $message)
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', ['message' => $message->load('product', 'user')]);
    }

    public function unread(ContactMessage $message)
    {
        $message->update(['read_at' => null]);

        return redirect()->route('admin.messages.index')->with('success', 'Mensagem marcada como não lida.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Mensagem excluída.');
    }
}
