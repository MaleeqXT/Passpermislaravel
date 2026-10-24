<?php

return [
    'google_review_url' => env('GOOGLE_REVIEW_URL'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:5173')),
    // Legacy first-hour offers are retained as a safe fallback while all new
    // offers are identified by their driving-package metadata.
    'before_training_first_hour_offer_ids' => array_values(array_filter(explode(',', env(
        'SATISFACTION_FIRST_HOUR_OFFER_IDS',
        '84aeacd3-a396-11f1-a172-9840bb4923f7,9eec8bb6-af81-4846-b729-5c646ead54f4'
    )))),
    // Percentage of the purchased driving package that must be completed
    // before the During-training survey is unlocked. Defaults to 75%.
    'during_training_threshold_percent' => (float) env('SATISFACTION_DURING_THRESHOLD_PERCENT', 75),
];
