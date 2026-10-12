<?php

namespace App\Http\Requests;

use App\SeasonDefinition;
use App\ThemePreference;
use App\TreePlacementStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Only the signed-in owner reaches this request (the auth middleware guards the route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional, so the theme switcher can send the theme alone.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', Rule::enum(ThemePreference::class)],
            'birth_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'season_definition' => ['sometimes', Rule::enum(SeasonDefinition::class)],
            'tree_placement' => ['sometimes', Rule::enum(TreePlacementStyle::class)],
            'show_excerpts' => ['sometimes', 'boolean'],
            'journal_timezone_confirmed' => ['sometimes', 'boolean'],
        ];
    }
}
