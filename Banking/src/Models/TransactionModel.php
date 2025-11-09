<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\Banking\Enums\TransactionTypeEnum;
use App\PersonalData\Models\UserModel;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string $account_id
 * @property string $user_id
 * @property float $amount
 * @property DateTime $date
 * @property TransactionTypeEnum $type
 * @property boolean $verified
 * @property null|string $load_id
 * @property null|string $operation_code
 * @property null|string $short_desc
 * @property null|string $desc
 * @property string|null $destination
 * @property int|null $mcc
 * @property string|null $category_id
 * @property string|null $code_id
 * @property string|null $mcc_reason
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 *
 * @property AccountModel $account
 * @property TransactionCategoryModel $category
 * @property TransactionCodeModel $code
 */
final class TransactionModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'transactions';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'desc',
        'short_desc',
        'operation_code',
        'amount',
        'date',
        'type',
        'destination',
        'mcc',
        'order',
        'category_id',
        'code_id',
        'color',
        'load_id',
        'user_id',
        'mcc_reason',
        'verified',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'mcc' => 'int',
        'order' => 'int',
        'amount' => 'float',
        'date' => 'datetime:Y-m-d H:i:s',
        'type' => TransactionTypeEnum::class,
        'verified' => 'boolean',
    ];

    public function account(): BelongsTo {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function category(): BelongsTo {
        return $this->belongsTo(TransactionCategoryModel::class, 'category_id');
    }

    public function code(): BelongsTo {
        return $this->belongsTo(TransactionCodeModel::class, 'code_id');
    }

    // небольшой костылек для автокомплита
    public function scopeWithRowNumber(Builder $query, string $column = 'id') {
        $sub = static::selectRaw("*, row_number() OVER (PARTITION BY transactions.$column ORDER BY transactions.$column) as row_number")->toSql();
        $query->from(DB::raw("({$sub}) as transactions"));
    }
}
