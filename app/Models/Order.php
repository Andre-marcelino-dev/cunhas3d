<?php

namespace App\Models;

use App\Services\StoreNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUS_PENDING = 'pendente';
    public const STATUS_PAID = 'pago';
    public const STATUS_PRODUCTION = 'em_producao';
    public const STATUS_SHIPPED = 'enviado';
    public const STATUS_DELIVERED = 'entregue';
    public const STATUS_CANCELED = 'cancelado';

    public const STATUSES = [
        self::STATUS_PENDING => 'Aguardando pagamento',
        self::STATUS_PAID => 'Pagamento aprovado',
        self::STATUS_PRODUCTION => 'Em produção',
        self::STATUS_SHIPPED => 'Enviado',
        self::STATUS_DELIVERED => 'Entregue',
        self::STATUS_CANCELED => 'Cancelado',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'amber',
        self::STATUS_PAID => 'emerald',
        self::STATUS_PRODUCTION => 'violet',
        self::STATUS_SHIPPED => 'sky',
        self::STATUS_DELIVERED => 'green',
        self::STATUS_CANCELED => 'rose',
    ];

    protected $fillable = [
        'code', 'user_id', 'status', 'subtotal', 'shipping', 'total',
        'name', 'email', 'phone', 'cpf', 'cep', 'address', 'number', 'complement',
        'district', 'city', 'state', 'notes',
        'mp_preference_id', 'mp_payment_id', 'payment_status', 'payment_method', 'paid_at',
        'tracking_code', 'stock_restored',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'stock_restored' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'zinc';
    }

    public function changeStatus(string $status, ?string $note = null): void
    {
        if ($this->status === $status) {
            return;
        }

        $previous = $this->status;

        $this->update(['status' => $status]);
        $this->histories()->create(['status' => $status, 'note' => $note]);

        // Acabou de ser pago (pelo Mercado Pago ou pelo admin): avisa o cliente e a loja por e-mail.
        // Só acontece uma vez por pedido, porque daqui pra frente o status não volta a ser "pendente".
        if ($previous === self::STATUS_PENDING && $status === self::STATUS_PAID) {
            app(StoreNotifier::class)->orderPaid($this);
        }
    }

    /**
     * Cancela o pedido e devolve os itens ao estoque (o estoque é reservado na criação do pedido).
     */
    public function cancel(?string $note = null): void
    {
        DB::transaction(function () use ($note) {
            if (! $this->stock_restored) {
                foreach ($this->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                    if ($item->product_variant_id) {
                        ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                    }
                }
                $this->update(['stock_restored' => true]);
            }

            $this->changeStatus(self::STATUS_CANCELED, $note ?? 'Pedido cancelado');
        });
    }

    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function generateCode(): string
    {
        $next = (static::max('id') ?? 0) + 1;

        return 'C3D-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
