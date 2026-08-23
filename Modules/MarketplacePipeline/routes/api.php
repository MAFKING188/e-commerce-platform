<?php

use Illuminate\Support\Facades\Route;

// No MarketplacePipeline API yet — commerce flows are web-session + CSRF
// protected by design. Add token-scoped endpoints only with a real consumer.
