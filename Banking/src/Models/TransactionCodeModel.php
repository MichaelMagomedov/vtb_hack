<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use App\PersonalData\Models\UserModel;
use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $code
 * @property string $name
 * @property string $category_id
 * @property string|null $desc
 * @property TransactionCategoryModel|null $category
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class TransactionCodeModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'transaction_codes';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'desc',
        'category_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'code' => 'int',
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(TransactionCategoryModel::class, 'category_id');
    }
}
