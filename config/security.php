<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PIN session lifetime
    |--------------------------------------------------------------------------
    |
    | Minutes a successful PIN verification remains valid for the current
    | access token. Expired sessions must be verified again.
    |
    */

    'pin_session_minutes' => (int) env('PIN_SESSION_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | PIN attempt lockout
    |--------------------------------------------------------------------------
    */

    'pin_max_attempts' => (int) env('PIN_MAX_ATTEMPTS', 5),

    'pin_lock_minutes' => (int) env('PIN_LOCK_MINUTES', 15),

];
