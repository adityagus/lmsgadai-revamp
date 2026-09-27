<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('quiz:expire')->everyMinute()->withoutOverlapping();
