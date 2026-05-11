<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Generate the next 14 days of trips, every day at 02:00 Asia/Colombo.
Schedule::command('trips:generate')->dailyAt('02:00')->timezone('Asia/Colombo');
