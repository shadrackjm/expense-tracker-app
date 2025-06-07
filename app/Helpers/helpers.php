<?php

// helpers.php
function app_currency() {
    return \App\Models\Setting::get('currency_symbol', 'Tsh');
}