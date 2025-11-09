<?php

declare(strict_types=1);

namespace App\Banking\Models;

use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $alias
 * @property string $icon
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class CurrencyModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'currencies';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'alias',
        'icon',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


}
