<?php

return [
    'job_exceptions' => [
        'parse_transactions_extension_exception' => '⚠️ Only PDF files are allowed. Please upload the file in the correct format and try again.️',
        'save_account_data_unknown_exception' => '⚠️ Unable to recognize data from file. Please try to upload file again. If the error persists, please contact support.',
        'save_account_number_exception' => '⚠️ Failed to recognize the account number. Please try to upload file again. If the error persists, please contact support.',
        'save_operations_exception' => '⚠️ Failed to recognize account transactions. Please to upload file again. If the error persists, please contact support',
        'success_parse_count_exception' => '⚠️ You can upload no more than :count times per hour for banking account, please wait :minutes minutes and try uploading again'
    ],
    'parse_pending_messages' => [
        'one' => '🕛 Loading data continues',
        'two' => '🕦 Almost done',
        'three' => '🕖 Finishing the download',
    ],
    'save_future_operation' => 'you cant create future transactions, please specify them in the "Expense and Income Templates" section',
    'success_parsed' => '💎 Your data has been successfully uploaded! You can view the result in your personal account',
    'transaction' => 'Passed transaction not found'
];
