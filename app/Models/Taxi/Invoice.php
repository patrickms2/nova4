<?php

namespace App\Models\Taxi;

use App\Enums\PagoEstado;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Jobs\GenerateInvoice;

/**
 * App\Models\Invoice
 *
 * @property int $id
 * @property string $invoice_id
 * @property int $client_id
 * @property string $invoice_date
 * @property string $due_date
 * @property float|null $amount
 * @property float|null $final_amount
 * @property int $discount_type
 * @property float $discount
 * @property string|null $note
 * @property string|null $term
 * @property Setting|mixed $currency_id
 * @property int|null $template_id
 * @property int $recurring
 * @property int $status
 * @property int $recurring_status
 * @property int|null $recurring_cycle
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|AdminPayment[] $AdminPayment
 * @property-read int|null $admin_payment_count
 * @property-read Client $client
 * @property-read string $status_label
 * @property-read Collection|InvoiceItem[] $invoiceItems
 * @property-read int|null $invoice_items_count
 * @property-read InvoiceSetting|null $invoiceTemplate
 * @property-read Collection|Payment[] $payments
 * @property-read int|null $payments_count
 *
 * @method static Builder|Invoice newModelQuery()
 * @method static Builder|Invoice newQuery()
 * @method static Builder|Invoice query()
 * @method static Builder|Invoice whereAmount($value)
 * @method static Builder|Invoice whereClientId($value)
 * @method static Builder|Invoice whereCreatedAt($value)
 * @method static Builder|Invoice whereCurrencyId($value)
 * @method static Builder|Invoice whereDiscount($value)
 * @method static Builder|Invoice whereDiscountType($value)
 * @method static Builder|Invoice whereDueDate($value)
 * @method static Builder|Invoice whereFinalAmount($value)
 * @method static Builder|Invoice whereId($value)
 * @method static Builder|Invoice whereInvoiceDate($value)
 * @method static Builder|Invoice whereInvoiceId($value)
 * @method static Builder|Invoice whereNote($value)
 * @method static Builder|Invoice whereRecurring($value)
 * @method static Builder|Invoice whereRecurringCycle($value)
 * @method static Builder|Invoice whereRecurringStatus($value)
 * @method static Builder|Invoice whereStatus($value)
 * @method static Builder|Invoice whereTemplateId($value)
 * @method static Builder|Invoice whereTerm($value)
 * @method static Builder|Invoice whereUpdatedAt($value)
 *
 * @property string|null $last_recurring_on
 *
 * @method static Builder|Invoice whereLastRecurringOn($value)
 *
 * @property int|null $parent_id
 *
 * @method static Builder|Invoice whereParentId($value)
 */
class Invoice extends Model
{
    use HasFactory;

    public const SELECT_DISCOUNT_TYPE = 0;

    public const FIXED = 1;

    public const PERCENTAGE = 2;

    public const DISCOUNT_TYPE = [
        self::SELECT_DISCOUNT_TYPE => 'Select Discount Type',
        self::FIXED => 'Fixed',
        self::PERCENTAGE => 'Percentage',
    ];

    public const DRAFT = 0;

    public const UNPAID = 1;

    public const PAID = 2;

    public const PARTIALLY = 3;

    public const OVERDUE = 4;

    public const STATUS_ALL = 7;

    public const PROCESSING = 6;

    public const STATUS_ARR = [
        self::STATUS_ALL => 'Todas',
        self::DRAFT => 'Borrador',
        self::UNPAID => 'No pagada',
        self::PAID => 'Pagada',
        self::PARTIALLY => 'Parc. Pagada',
        self::OVERDUE => 'Pasada',
        self::PROCESSING => 'Procesando',
    ];

    public const RECURRING_OFF = 0;

    public const RECURRING_ON = 1;

    public const RECURRING_STATUS_ARR = [
        self::RECURRING_ON => 'On',
        self::RECURRING_OFF => 'Off',
    ];

    public const MONTHLY = 1;

    public const QUARTERLY = 2;

    public const SEMIANNUALLY = 3;

    public const ANNUALLY = 4;

    public const RECURRING_ARR = [
        self::MONTHLY => 'Monthly',
        self::QUARTERLY => 'Quarterly',
        self::SEMIANNUALLY => 'Semi Annually',
        self::ANNUALLY => 'Annually',
    ];

    public $table = 'invoices';

    protected $fillable = [
        'id',
        'usuario_id',
        'payment_qr_code_id',
        'amount',
        'final_amount',
        'serial',
        'serial_number',
        'customer_name',
        'customer_email',
        'product',
        'filename',
        'invoice_date',
        'status',
    ];
    protected $casts = [
        'amount_total' => 'float',
        'amount' => 'float',
        'status' => PagoEstado::class,
    ];
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->serial_number = str_pad((Invoice::max('serial_number') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
            $invoice->serial = 'ABC-' . $invoice->serial_number;
        });
        static::updated(function (Invoice $invoice) {
            ray($invoice);

            if($invoice->isDirty('amount') && $invoice->wasChanged('amount'))
                {
                    $invoice->update(['filename' => GenerateInvoice::dispatch($invoice)]);
                }
                //$invoice->update(['filename' => GenerateInvoice::dispatch($invoice)]);
        });
        static::created(function (Invoice $invoice) {
            GenerateInvoice::dispatch($invoice);
        });
    }


    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }


    public static function generateUniqueInvoiceId(): string
    {
        $invoiceId = mb_strtoupper(Str::random(6));
        while (true) {
            $isExist = self::whereInvoiceId($invoiceId)->exists();
            if ($isExist) {
                self::generateUniqueInvoiceId();
            }
            break;
        }

        return $invoiceId;
    }

    public function setInvoiceDateAttribute($value): void
    {
        $dateFormat = currentDateFormat();

        try {
            if (Carbon::hasFormat($value, 'Y-m-d')) {
                $this->attributes['invoice_date'] = $value;
            } else {

                $this->attributes['invoice_date'] = Carbon::createFromFormat(
                    $dateFormat,
                    $value
                )->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $this->attributes['invoice_date'] = null;
        }
    }


}
