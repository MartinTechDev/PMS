<?php

return [
    'base_url' => env('PMS_BASE_URL'),
    'rate_limit_per_second' => env('PMS_RATE_LIMIT', 2),
    'timeout' => env('PMS_TIMEOUT', 10),
    'retry_times' => env('PMS_RETRY_TIMES', 3),
];
