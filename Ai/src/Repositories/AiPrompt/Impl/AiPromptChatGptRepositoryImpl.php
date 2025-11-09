<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiPrompt\Impl;

use App\Ai\Entities\LoadEntity;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunnable;
use App\Ai\Repositories\AiPrompt\AiPromptRepository;
use App\Ai\Clients\ChatGptClient;
use App\Ai\Repositories\Ai\Dto\AssistantPromptInstructionDto;
use App\Banking\Enums\AccountTypeEnum;
use App\Banking\Enums\TransactionTypeEnum;
use Illuminate\Foundation\Bus\PendingChain;

/**
 * Все промпты МЕГО ЧУСТВИТЕЛЬНЫ так что как только вы его меняете
 * ПОЖАЛУЙСТА ОЧЕНЬ МНОГО РАЗ ПРОТЕСТИРУЙТЕ ВСЕ кейсы, которые придумаете !!!!
 */
final class AiPromptChatGptRepositoryImpl implements AiPromptRepository
{
    public function __construct(
        protected readonly ChatGptClient $client,
    )
    {
    }

    /**
     * @deprecated
     *
     * ВНИМАНИЕ! Этот метод не использует ассистента так что тут используется getCompletionJsonResponse
     * для использования file_search и использование ассистента см использование функции @getAssistantJsonResponse
     */
    public function distributeMccCodes(array $codes, array $categories): array
    {
        $csvCodes = arrayToCsv($codes);
        $csvCategories = implode(',', $categories);
        return $this->client->getCompletionJsonResponse(
            "Есть mcc коды в формате csv: $csvCodes . Есть категории перечисленные через запятую: $csvCategories .
            Распредели все " . count($codes) . " mcc коды по существующим категориям (без использования программирования),
            если нет подходящей категория, то придумай категорию сам. Названия категорий должны быть на русском языке.
            Ответ должен содержать " . count($codes) . " кодов",
            [
                'type' => "object",
                'properties' => [
                    'codes' => [
                        'type' => 'array',
                        'description' => 'Список mcc кодов',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'description' => 'Название mcc кода',
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'description' => 'Номер кода',
                                ],
                                'desc' => [
                                    'type' => 'string',
                                    'description' => 'Описание mcc кода',
                                ],
                                'category' => [
                                    'type' => 'string',
                                    'description' => 'Категория mcc кода',
                                ]
                            ],
                            'required' => ['name', 'code', 'category']
                        ]
                    ],
                ],
                'required' => ['codes']
            ]
        );
    }

    public function prepareTransactionsDataAndRunAction(string $pdfText, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain
    {
        $typesDescriptions = array_map(fn($type) => $type->value . '-' . $type->label(), TransactionTypeEnum::cases());
        $typesDescriptions = implode(',', $typesDescriptions);

        return $this->client->getAssistantJsonResponse(
            [
                // с текущей строки до строки, следующей операции - это что бы захватывать mcc код
                // ОЧЕНЬ ВАЖНО добавлять везде (без использования программирования) так как
                // CHAT GPT не будет пытаться применять  кривые ОЧЕНЬ долгие алгоритмы с запуском кода
                new AssistantPromptInstructionDto("
                    Есть выписка из банка, которая содержит все операции по счету за период:
                    $pdfText
                    Выдели из выписки все транзакции. Для каждой транзакции выдели (без использования программирования):
                        1) desc - полное описание начиная с текущей строки до строки, следующей операции,
                        2) shortDesc - краткое описание,
                        3) datetime - дата и время совершения в формате YYYY-MM-DD HH:mm:ss
                        4) amount - сумму(если это пополнение то вернуть положительно число, если списание, то вернуть отрицательное),
                        5) type - тип/вид операции где: " . implode(',', array_map(fn(TransactionTypeEnum $type) => $type->value . '-' . $type->label(), [
                            TransactionTypeEnum::SBP,
                            TransactionTypeEnum::SIMPLE,
                            TransactionTypeEnum::BETWEEN_ACCOUNTS
                        ])) . ",
                        6) operationCode - код операци,
                        7) merchantAlias - Торговец/продавец/место покупки/Merchant/Получатель платежа, верни его на том языке на котором он написан в выписке. Если не указан оставь пустым,
                        8) merchantColor - Найди фирменный цвет в формате hex из открытых источников для Торговца/продавца/места покупки/Merchant, если не указан оставь пустым. Не выводи а запомни результат",
                    // потенциально тут true не нужен (но хз почему с ним работает лучеще)
                    true
                ),
                // этот запрос я получил задав вопрос gpt пока он отрабатывает лучше всего
                new AssistantPromptInstructionDto(
                    "Извлеки четырехзначный код MCC из поля desc. Этот код может быть представлен в различных форматах
                     и может начинаться с MCC, но не обязательно (пример MCC5814). Если MCC код не найден в описании то:
                        1) Анализировать ключевые слова или фразы в описании, которые могут указывать на определенную категорию.
                        2) Использовать эти ключевые слова для выполнения поиска в векторном хранилище и нахождения наиболее подходящего MCC кода.
                    Добавить найденный MCC код для каждой операции в ответ. Если не удалось определить код оставь его пустым.
                    Добавить поле mccReason - краткое описание алгоритма как ты определил этот mcc код. Если поле mcc пустое (код не найден), то поле mccReason оставь пустым. Не выводи а запомни результат",
                    true
                ),
                new AssistantPromptInstructionDto('Вызови с получившимся json результатом функцию simple_function')
            ],
            [
                'type' => "object",
                'properties' => [
                    'operations' => [
                        'type' => 'array',
                        'description' => 'Список всех операций',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'amount' => [
                                    'type' => 'string',
                                    'description' => 'Сумма операции, число (если это пополнение то вернуть положительно число, если списание, то вернуть отрицательное)',
                                ],
                                'datetime' => [
                                    'type' => 'string',
                                    'description' => 'Время операции в формате в формате YYYY-MM-DD HH:mm:ss',
                                ],
                                'type' => [
                                    'type' => 'string',
                                    'description' => 'Один из видов операции: ' . $typesDescriptions,
                                    'enum' => array_column(TransactionTypeEnum::cases(), 'value')
                                ],
                                'shortDesc' => [
                                    'type' => 'string',
                                    'description' => 'Краткое описание операции',
                                ],
                                'desc' => [
                                    'type' => 'string',
                                    'description' => 'Описание операции',
                                ],
                                'operationCode' => [
                                    'type' => 'string',
                                    'description' => 'Код операции',
                                ],
                                'merchantAlias' => [
                                    'type' => 'string',
                                    'description' => 'Торговец/продавец/место покупки/Merchant/Получатель платежа',
                                ],
                                'merchantColor' => [
                                    'type' => 'string',
                                    'description' => 'Найди фирменный цвет в формате hex из открытых источников для Торговца/продавца/места покупки/Merchant',
                                ],
                                'mcc' => [
                                    'type' => 'string',
                                    'description' => 'Определи какой это mcc code (merchant category cod) и найди подходящий код из vector store transaction-codes.json в числовом формате',
                                ],
                                'mccReason' => [
                                    'type' => 'string',
                                    'description' => 'Краткое описание алгоритма как ты определил этот mcc код',
                                ]
                            ],
                            'required' => ['amount', 'dateTime', 'type', 'shortDesc']
                        ],
                    ]
                ],
                'required' => ['operations']
            ],
            $load,
            $afterJob
        );
    }

    public function prepareAccountDataAndRunAction(string $pdfText, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain
    {
        $typesDescriptions = array_map(fn($type) => $type->value . '-' . $type->label(), AccountTypeEnum::cases());
        $typesDescriptions = implode(',', $typesDescriptions);

        return $this->client->getAssistantJsonResponse(
            [
                // ОЧЕНЬ ВАЖНО добавлять везде (без использования программирования) так как
                // CHAT GPT не будет пытаться применять  кривые ОЧЕНЬ долгие алгоритмы с запуском кода
                new AssistantPromptInstructionDto("
                    Есть выписка из банка, которая содержит все операции по счету за период:
                    $pdfText
                    Верни мне (без использования программирования):
                        1) number - номер счета,
                        2) start_date - начальную дату и
                        3) end_date - конечную дату периода за который сделана эта выписка в формате YYYY-MM-DD ,
                        4) start_balance - баланс счета на начало периода в формате числа
                        5) end_balance - баланс счета на конец периода в формате числа,
                        6) name - название счет,
                        7) bank_alias - Определи какой это банк и найди alias из vector store banks.json,
                        8) currency_code - Определи какая это валюта и найди code из vector store currencies.json,
                        9) type - тип банковского счета где: " . implode(',', array_map(fn(AccountTypeEnum $type) => $type->value . '-' . $type->label(), AccountTypeEnum::cases())) . ",
                    Не выводи а запомни результат",
                    true
                ),
                // поиск по vector store выносим в отдельную инструкцию так как таким макаром гораздо лучше
                new AssistantPromptInstructionDto(
                    'Найди по подходящему bank_alias ид банка из vectorStore banks.json:
                         1) bank_id - замени в результирующем json поле bank_alias на bank_id с этим id банка,
                         2) bank_reason - краткое описание, почему выбран именно этот bank_id Если bank_id не найден оставь поле bank_reason так же пустым,
                    Не выводи а запомни результат',
                    true
                ),
                // этап проверки добавляет точность поиска
                new AssistantPromptInstructionDto(
                    'Проверь правильный ли ты нашел bank_id из vectorStore. Если есть ошибка исправь ее в результате. Не выводи а запомни результат',
                    true
                ),
                new AssistantPromptInstructionDto(
                    'Найди по подходящему currency_code ид валюты из vectorStore currencies.json:
                         1) currency_id - замени в результирующем json поле currency_code на currency_id с этим id валюты.
                         2) currency_reason - краткое описание, почему выбран именно этот currency_id. Если currency_id не найдено оставь поле currency_reason так же пустым,
                    Не выводи а запомни результат',
                    true
                ),
                // этап проверки добавляет точность поиска
                new AssistantPromptInstructionDto(
                    'Проверь правильный ли ты нашел currency_id из vectorStore. Если есть ошибка исправь ее в результате. Не выводи а запомни результат',
                    true
                ),
                new AssistantPromptInstructionDto('Вызови с получившимся json результатом функцию simple_function')
            ],
            [
                'type' => "object",
                'properties' => [
                    'number' => [
                        'type' => 'string',
                        'description' => 'Номер счета',
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Название счета',
                    ],
                    'bank_id' => [
                        'type' => 'string',
                        'description' => 'Определи какой это банк и найди подходящий id банка из vector store banks.json в формате uuid',
                    ],
                    'bank_reason' => [
                        'type' => 'string',
                        'description' => 'Краткое описание, почему выбран именно этот bank_id',
                    ],
                    'currency_id' => [
                        'type' => 'string',
                        'description' => 'Определи какая это валюта и найди подходящий id валюты из vector store currencies.json в формате uuid',
                    ],
                    'currency_reason' => [
                        'type' => 'string',
                        'description' => 'Краткое описание, почему выбран именно этот currency_id',
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Начальная дата периода выписки в формате YYYY-MM-DD',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Конечная дата периода выписки в формате YYYY-MM-DD',
                    ],
                    'start_balance' => [
                        'type' => 'string',
                        'description' => 'Баланс счета на начало периода, число',
                    ],
                    'end_balance' => [
                        'type' => 'string',
                        'description' => 'Баланс счета на конец периода, число',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Один из типов банковского счета: ' . $typesDescriptions,
                        'enum' => array_column(AccountTypeEnum::cases(), 'value')
                    ],
                ],
                'required' => ['number', 'bank_id', 'currency_id']
            ],
            $load,
            $afterJob,
        );
    }

    public function prepareIncomesAndRunAction(array $incomes, array $months, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain
    {
        return $this->client->getAssistantJsonResponse(
            [
                // "Описание операции  может различаться только датами или цифрами" - например если договор по которому поступает зачисление один и тот же но номер его меняется
                // ОЧЕНЬ ВАЖНО добавлять везде (без использования программирования) так как
                // CHAT GPT не будет пытаться применять  кривые ОЧЕНЬ долгие алгоритмы с запуском кода
                new AssistantPromptInstructionDto("
                  Есть выписка из банка в формате CSV, содержащая информацию о всех пополнениях счета за определенный период:
                  " . arrayToCsv($incomes) . "
                  Необходимо, на основе переданной выписки, составить шаблон(план) доходов для одного месяца для каждой даты (без использования программирования).
                  Для каждого номера дня от 1 до 31 числа вычисли какие регулярные доходы встречаются в этот номер дня в переданных операция (без использования программирования).
                      1) Считать поступление регулярным, если оно происходит каждый месяц (более 70% месяцев содержит этот доход):" . implode(',', $months) . ",
                      2) При вычислении регулярности поступления, учитывать не только размер поступления, но и описание операции. Описание операции должно быть одинаковым и может различаться только датами или цифрами.
                      3) При вычислении регулярности поступления, учитывать что при схожем описание дата поступления из месяца в месяц может отличатся на 1-4 дня.
                      4) Поступления с одинаковым описанием в один и тот же месяц считать уникальными(разными) - из разных категорий, не суммировать их
                      5) Если это перевод через 'Систему быстрых платежей', то при вычислении регулярности необходимо, чтобы совпадал отправитель и разница между поступлениями не превышала 1%-2%.
                  Алгоритм:
                      1) Проанализировать выписку, выделив уникальные описания и отправителей.
                      3) Определить в каких месяцах, встречается каждое поступление.
                      4) Учитывать только те поступления, которые происходят каждый месяц (более 70% месяцев содержит этот доход):" . implode(',', $months) . ",
                      6) Для каждого поступления (day_number): вычисли наиболее частый день (средний,если день меняется в каждом месяце) если дни распределены равномерно, выбрать наименьший номер дня,
                      8) Для каждого поступления (description): Кратко опиши, за что было поступление, для Системы быстрых платежей дополни description отправителем,
                      9) Для каждого поступления (reason): Опиши в какие месяцы встречалось это пополнение и какая была сумма пополнений в формате: месяц: <дата>, сумма <сумма> | месяц: <дата>, сумма <сумма> итд,
                  Для каждого поступления верни:
                      2) day_number - Номер дня в месяце,
                      3) description - Краткое описание, за что было поступление,
                      4) reason - Описание в какие месяцы встречалось это пополнение,
                  Игнорировать поступления, которые встречаются нерегулярно. Не выводи а запомни результат",
                ),
                // иногда некоторые поля лучше получать отдельным шагом так chatgpt их считает более точно
                new AssistantPromptInstructionDto(
                    'Для каждого дохода вычисли средний размер дохода (amount) используя данные из поля reason
                     Данные в этом поле расположены в формате <дата>, сумма <сумма> | месяц: <дата>, сумма <сумма> итд.
                     Возьму сумму доходов в поле reason и раздели на количество месяцев перечисленные в reason.
                     Дополни поле reason получившимся числом в формате перепроверка:<результат>
                     Добавь поле amount в итоговом json результате. Не выводи а запомни результат',
                ),
                new AssistantPromptInstructionDto('Вызови с получившимся json результатом функцию simple_function')
            ],
            [
                'type' => "object",
                'properties' => [
                    'operations' => [
                        'type' => 'array',
                        'description' => 'Список всех операций',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'day_number' => [
                                    'type' => 'integer',
                                    'description' => 'Номер дня в месяце',
                                ],
                                'amount' => [
                                    'type' => 'integer',
                                    'description' => 'Размер поступления',
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'Краткое описание, за что было поступление',
                                ],
                                'reason' => [
                                    'type' => 'string',
                                    'description' => 'Описание в какие месяцы встречалось это пополнение',
                                ],
                            ],
                            'required' => ['day_number', 'amount', 'description', 'reason']
                        ],
                    ]
                ],
                'required' => ['operations']
            ],
            $load,
            $afterJob,
        );
    }

    public function prepareMonthlyExpensesAndRunAction(array $expenses, array $months, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain
    {
        return $this->client->getAssistantJsonResponse(
            [
                // "Описание операции  может различаться только датами или цифрами" - например если договор по которому поступает зачисление один и тот же но номер его меняется
                // ОЧЕНЬ ВАЖНО добавлять везде (без использования программирования) так как
                // CHAT GPT не будет пытаться применять  кривые ОЧЕНЬ долгие алгоритмы с запуском кода
                new AssistantPromptInstructionDto("
                  Есть выписка из банка в формате CSV, содержащая информацию о всех расходах счета за определенный период:
                  " . arrayToCsv($expenses) . "
                  Необходимо, на основе переданной выписки, составить шаблон(план) расходов для одного месяца для каждой даты.
                  Для каждого номера дня от 1 до 31 числа вычисли какие регулярные расходы встречаются в этот номер дня в переданных операция (без использования программирования).
                      1) Считать расход регулярным, если он происходит каждый месяц (более 70% месяцев содержит этот расход):" . implode(',', $months) . ",
                      2) При вычислении регулярности расхода, учитывать что если описание операций сходится по категориям (например фастфуд и рестораны) считать такие описания одинаковыми
                      4) При вычислении регулярности расхода, учитывать что при схожем описание дата расхода из месяца в месяц может отличатся на 1-4 дня.
                      5) Расходы с одинаковым описанием в один и тот же месяц считать уникальными(разными) - из разных категорий, не суммировать их
                      6) Если это перевод через 'Систему быстрых платежей', то при вычислении регулярности необходимо, чтобы совпадал получатель и разница между расходами не превышала 1%-2%.
                  Алгоритм:
                      1) Проанализировать выписку, выделив уникальные описания и получателей/продавцов.
                      2) Определить в какие дни, встречается каждый расход.
                      4) Учитывать только те расходы, которые происходят каждый месяц (более 70% месяцев содержит этот доход):" . implode(',', $months) . ",
                      5) Для каждого расхода (day_number): вычисли наиболее частый день (брать средний только в случае если день меняется в каждом месяце),
                      7) Для каждого расхода (description): Кратко опиши, за что было поступление, для Системы быстрых платежей дополни description получателем,
                      8) Для каждого расхода (reason): Опиши в какие месяцы встречался этот расход и какая была сумма расходв в формате: месяц: <дата>, сумма <сумма> | месяц: <дата>, сумма <сумма> итд,
                  Для каждого расхода верни:
                      2) day_number - Номер дня в месяце,
                      3) description - Краткое описание, за что был расход,
                      4) reason - Описание в какие месяцы встречалось этот расход,
                  Игнорировать расходы, которые встречаются нерегулярно. Не выводи а запомни результат",
                ),
                // иногда некоторые поля лучше получать отдельным шагом так chatgpt их считает более точно
                new AssistantPromptInstructionDto(
                    'Для каждого расхода вычисли средний размер расхода (amount) используя данные из поля reason
                     Данные в этом поле расположены в формате <дата>, сумма <сумма> | месяц: <дата>, сумма <сумма> итд.
                     Возьму сумму расходов в поле reason и раздели на количество месяцев перечисленные в reason.
                     Дополни поле reason получившимся числом в формате перепроверка:<результат>
                     Добавь поле amount в итоговом json результате Не выводи а запомни результат',
                ),
                new AssistantPromptInstructionDto('Вызови с получившимся json результатом функцию simple_function')
            ],
            [
                'type' => "object",
                'properties' => [
                    'operations' => [
                        'type' => 'array',
                        'description' => 'Список всех операций',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'day_number' => [
                                    'type' => 'integer',
                                    'description' => 'Номер дня в месяце',
                                ],
                                'amount' => [
                                    'type' => 'integer',
                                    'description' => 'Размер расхода',
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'Краткое описание, за что было расход',
                                ],
                                'reason' => [
                                    'type' => 'string',
                                    'description' => 'Описание в какие месяцы встречался это расход',
                                ],
                            ],
                            'required' => ['day_number', 'amount', 'description', 'reason']
                        ],
                    ]
                ],
                'required' => ['operations']
            ],
            $load,
            $afterJob,
        );
    }
}
