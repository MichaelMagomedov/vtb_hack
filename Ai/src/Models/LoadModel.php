<?php

declare(strict_types=1);

namespace App\Ai\Models;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Enums\LoadTypeEnum;
use App\Banking\Models\TransactionModel;
use App\Recommendation\Models\UserExpensesTemplateModel;
use App\Root\Models\Model;
use App\Root\Utils\HistoryFields\Traits\UpdateHistoryFields;
use DateTime;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $user_id
 * @property string $chat_id
 * @property string|null $reason
 * @property string|null $account_id
 * @property string|null $last_message_id
 * @property LoadStatusEnum $status
 * @property string|null $sys_reason
 * @property integer $input_chars_count
 * @property integer $input_words_count
 * @property integer $output_chars_count
 * @property integer $output_words_count
 * @property LoadTypeEnum $type
 * @property DateTime|null $deleted_at
 * @property DateTime|null $created_at
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_by
 * @property DateTime|null $updated_by
 * @property DateTime|null $deleted_by
 */final class LoadModel extends Model
{
    use UpdateHistoryFields, SoftDeletes;

    public $incrementing = false;

    protected $table = 'loads';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'status',
        'user_id',
        'chat_id',
        'reason',
        'last_message_id',
        'account_id',
        'sys_reason',
        'input_chars_count',
        'input_words_count',
        'output_chars_count',
        'output_words_count',
        'type',
        'deleted_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => LoadStatusEnum::class,
        'type' => LoadTypeEnum::class,
        'input_chars_count' => 'integer',
        'input_words_count' => 'integer',
        'output_chars_count' => 'integer',
        'output_words_count' => 'integer',
    ];

    // это системная инфа
    public function threads(): HasMany {
        return $this->hasMany(AiThreadModel::class, 'load_id');
    }

    // это если load был запущен для парсинга транзакций
    public function transactions(): HasMany {
        return $this->hasMany(TransactionModel::class, 'load_id');
    }

    // это если load был запущен для составления шаблонов
    public function expensesTemplates(): HasMany {
        return $this->hasMany(UserExpensesTemplateModel::class, 'load_id');
    }
}
