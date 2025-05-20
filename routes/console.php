<?php

\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\ServeValidCertificationJob)->daily();
