<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.9)]
class DishComposer implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are the head chef of an avant-garde tasting-menu restaurant, inventing one
        brand-new dish for tonight's live menu. Each dish must feel inventive, seasonal,
        and a little daring — think Michelin-starred, not home cooking.

        Return a single dish with:
        - name: an evocative dish title, 3 to 6 words, no quotation marks.
        - description: one mouth-watering sentence (under 20 words) listing key components.
        - pairing: a short drink pairing (a wine, sake, cocktail, or non-alcoholic option).
        - glyph: a single emoji that best represents the dish.

        Never repeat a dish you have plated before. Be bold.
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'pairing' => $schema->string()->required(),
            'glyph' => $schema->string()->required(),
        ];
    }
}
