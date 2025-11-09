<?php

namespace App\Banking\Services\TransactionCode\Impl;

use App\Banking\Entities\TransactionCategoryEntity;
use App\Banking\Entities\TransactionCodeEntity;
use App\Banking\Repositories\TransactionCategory\TransactionCategoryRepository;
use App\Banking\Repositories\TransactionCode\TransactionCodeRepository;
use App\Banking\Services\TransactionCode\TransactionCodeService;
use Ramsey\Uuid\Uuid;

class TransactionCodeServiceImpl implements TransactionCodeService
{
    public function __construct(
        private readonly TransactionCodeRepository     $transactionCodeRepository,
        private readonly TransactionCategoryRepository $transactionCategoryRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveCodes(array $codes): array {
        $savedCodes = [];
        $categoryNameIdMap = [];
        foreach ($this->transactionCategoryRepository->findAll() as $category) {
            $categoryNameIdMap[mb_strtolower($category->getName())] = $category->getId();
        }

        foreach ($codes as $codeData) {
            $existsCode = $this->transactionCodeRepository->findByCode($codeData->getCode());
            if ($existsCode !== null) {
                $savedCodes[] = $existsCode;
                continue;
            }
            // пытаемся найти категория по имени которое подставил AI
            $categoryId = $categoryNameIdMap[mb_strtolower($codeData->getCategoryName())] ?? null;
            if ($categoryId === null) {
                $color = substr(md5(rand()), 0, 6);
                $newCategory = $this->transactionCategoryRepository->save(new TransactionCategoryEntity(
                    Uuid::uuid4()->toString(),
                    $codeData->getCategoryName(),
                    "#$color"
                ));
                $categoryNameIdMap[mb_strtolower($newCategory->getName())] = $newCategory->getId();
                $categoryId = $newCategory->getId();
            }
            $savedCodes[] = $this->transactionCodeRepository->save(new TransactionCodeEntity(
                Uuid::uuid4()->toString(),
                $codeData->getCode(),
                $categoryId,
                $codeData->getName(),
                $codeData->getDesc()
            ));
        }

        return $savedCodes;
    }
}
