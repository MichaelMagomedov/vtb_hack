<?php

declare(strict_types=1);

namespace App\Ai\Models;

use App\Ai\Models\LoadModel;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $run_id
 * @property string $thread
 * @property string/null $function_call_params
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */
final class AiThreadModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'ai_threads';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'run_id',
        'load_id',
        'thread',
        'function_call_params',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
    ];

    /** loadModel так как метод load занят */
    public function loadModel(): BelongsTo {
        return $this->belongsTo(LoadModel::class, 'load_id');
    }
}
