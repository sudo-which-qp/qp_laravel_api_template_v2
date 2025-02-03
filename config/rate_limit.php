<?php

return [
    // Default maximum number of requests
    'max_requests' => env('RATE_LIMIT_MAX_REQUESTS', 60),

    // Default time window in minutes
    'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),
];
