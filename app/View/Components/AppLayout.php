<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppLayout extends Component
{
    /** @var list<string> Vite paths for every enabled module's scss/js asset pair */
    public array $moduleAssets = [];

    public function __construct()
    {
        $moduleStatuses = json_decode((string) file_get_contents(base_path('modules_statuses.json')), true);

        foreach (array_keys($moduleStatuses ?? []) as $module) {
            if (($moduleStatuses[$module] ?? false) !== true || ! is_dir(base_path("Modules/{$module}"))) {
                continue;
            }

            $this->moduleAssets[] = "Modules/{$module}/resources/assets/scss/app.scss";
            $this->moduleAssets[] = "Modules/{$module}/resources/assets/js/app.js";
        }
    }

    public function render(): View|Closure|string
    {
        return view('layouts.app');
    }
}