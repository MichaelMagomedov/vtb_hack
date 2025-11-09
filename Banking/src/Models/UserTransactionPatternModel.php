<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\PersonalData\Models\UserModel;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sentry\Tracing\Transaction;

/**
 * @property int $id
 * @property string $destination
 * @property string $user_id
 * @property string $from_transaction_id
 * @property string|null $category_id
 * @property string|null $code_id
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 *
 * @property UserModel $user
 * @property Transaction $transaction
 * @property TransactionCategoryModel $category
 * @property TransactionCodeModel $code
 */
final class UserTransactionPatternModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'user_transaction_patterns';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'destination',
        'user_id',
        'from_transaction_id',
        'category_id',
        'code_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(TransactionCategoryModel::class, 'category_id');
    }

    public function transaction(): BelongsTo {
        return $this->belongsTo(TransactionModel::class, 'from_transaction_id');
    }

    public function code(): BelongsTo {
        return $this->belongsTo(TransactionCodeModel::class, 'code_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
