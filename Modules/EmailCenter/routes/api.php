<?php

use Illuminate\Support\Facades\Route;

// No public EmailCenter API yet. Compose/send flows are web-session + CSRF
// protected by design; add token-scoped endpoints only with a real consumer.
