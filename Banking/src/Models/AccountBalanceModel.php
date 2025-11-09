<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\PersonalData\Models\UserModel;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property float $balance
 * @property DateTime $balance_date
 * @property string $user_id
 * @property string|null $account_id
 * @property string|null $load_id
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class AccountBalanceModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'account_balances';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'balance',
        'balance_date',
        'order',
        'user_id',
        'account_id',
        'load_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'balance' => 'float',
        'order' => 'int',
        'balance_date' => 'datetime:Y-m-d H:i:s',
    ];

    public function account(): BelongsTo {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function uesr(): BelongsTo {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
