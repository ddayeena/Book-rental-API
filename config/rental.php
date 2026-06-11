<?php

return [
    'penalty_multiplier'  => env('RENTAL_PENALTY_MULTIPLIER', 2),
    'lost_processing_fee' => env('RENTAL_LOST_PROCESSING_FEE', 100),
    'auto_cancel_days'    => env('RENTAL_AUTO_CANCEL_DAYS', 5),
];