<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\Banking\Enums\AccountTypeEnum;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use App\PersonalData\Models\UserModel;
use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property  string $id
 * @property string $user_id
 * @property string $number
 * @property string $name
 * @property float $balance
 * @property int $order
 * @property string $currency_id
 * @property string|null $currency_reason
 * @property string|null $bank_id
 * @property string|null $bank_reason
 * @property AccountTypeEnum|null type
 * @property CurrencyModel $currency
 * @property BankModel|null bank
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class AccountModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'accounts';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'bank_id',
        'currency_id',
        'number',
        'name',
        'type',
        'order',
        'bank_reason',
        'currency_reason',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'type' => AccountTypeEnum::class,
        'order' => 'int',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function bank(): BelongsTo {
        return $this->belongsTo(BankModel::class, 'bank_id');
    }

    public function currency(): BelongsTo {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function transactions(): HasMany {
        return $this->hasMany(TransactionModel::class, 'account_id');
    }
}
