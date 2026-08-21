<?php

namespace Modules\EmailCenter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EmailCenter\Models\EmailTemplate;
use Modules\IdentityAccess\Models\User;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'subject' => fake()->sentence(5),
            'body_markdown' => fake()->paragraphs(3, true),
            'created_by' => null,
        ];
    }
}