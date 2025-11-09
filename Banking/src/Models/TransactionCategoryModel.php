<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $color
 * @property string|null $logo
 * @property int|null $order
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class TransactionCategoryModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'transaction_categories';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'logo',
        'name',
        'color',
        'order',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function codes(): HasMany {
        return $this->hasMany(TransactionCodeModel::class, 'category_id');
    }

    public function transactions(): HasMany {
        return $this->hasMany(TransactionModel::class, 'category_id');
    }
}
