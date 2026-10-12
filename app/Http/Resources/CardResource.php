<?php

namespace App\Http\Resources;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Card
 */
class CardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'title' => $this->title,
            'arcana' => $this->arcana,
            'suit' => $this->suit?->value,
            'suitLabel' => $this->suit?->label(),
            'world' => $this->suit?->world(),
            'rank' => $this->rank,
            'number' => $this->number,
            'roman' => $this->romanNumeral(),
            'element' => $this->element,
            'subElement' => $this->sub_element,
            'hebrewLetter' => $this->hebrew_letter,
            'planet' => $this->planet,
            'sign' => $this->sign,
            'decan' => $this->decan,
            'zodiacSpan' => $this->zodiac_span_from === null
                ? null
                : ['from' => $this->zodiac_span_from, 'to' => $this->zodiac_span_to],
            'quadrant' => $this->quadrant,
            'sephira' => $this->sephira?->value,
            'sephiraLabel' => $this->sephira?->label(),
            'treePath' => $this->tree_path,
            'thothreadingsUrl' => $this->thothreadings_url,
            'personalNote' => $this->personal_note,
            'imageUrl' => $this->imageUrl(),
            'thumbnailUrl' => $this->thumbnailUrl(),
        ];
    }
}
