<?php

\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\ServeValidCertificationJob)->daily();

// Отдельное время от ServeValidCertificationJob — CRL-проверка ходит в сеть
// и для большого реестра может идти существенно дольше, не должна с ней конкурировать.
\Illuminate\Support\Facades\Schedule::command('certificates:check-revocation')->dailyAt('01:00');
