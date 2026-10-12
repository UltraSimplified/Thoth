<?php

namespace App\Models;

use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * Find a theme by name, ignoring case, or create it. Surrounding spaces are dropped.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = Str::squish($name);

        return self::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first() ?? self::create(['name' => $name]);
    }

    /**
     * @return BelongsToMany<Day, $this>
     */
    public function days(): BelongsToMany
    {
        return $this->belongsToMany(Day::class);
    }
}
