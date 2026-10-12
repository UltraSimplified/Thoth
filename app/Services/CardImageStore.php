<?php

namespace App\Services;

use App\Models\Card;
use Illuminate\Support\Facades\Storage;

/**
 * Turns a scan of a card into the two WebP files the app shows: the full image and the thumbnail. Used by the
 * cards:import-images command and by uploads on the day page, so both produce the same files.
 */
final class CardImageStore
{
    /**
     * Width to height of a tarot card, used to crop away any border around the scan.
     */
    public const CARD_RATIO = 0.6;

    private const FULL_WIDTH = 800;

    private const THUMBNAIL_WIDTH = 200;

    private const QUALITY = 85;

    /**
     * Crop the scan to the card's proportions and store it as the card's image. Returns false when GD cannot read the bytes.
     */
    public function storeScan(Card $card, string $bytes, float $ratio = self::CARD_RATIO): bool
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return false;
        }

        $cropped = $this->cropToRatio($image, $ratio);

        $this->store($this->scaleTo($cropped, self::FULL_WIDTH), 'cards/'.$card->slug.'.webp');
        $this->store($this->scaleTo($cropped, self::THUMBNAIL_WIDTH), 'cards/thumbs/'.$card->slug.'.webp');

        $card->increment('image_version');

        return true;
    }

    /**
     * Crop the centre of the scan to the card's proportions, dropping any border around it.
     */
    private function cropToRatio(\GdImage $image, float $ratio): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width / $height > $ratio) {
            $croppedWidth = (int) round($height * $ratio);
            $croppedHeight = $height;
        } else {
            $croppedWidth = $width;
            $croppedHeight = (int) round($width / $ratio);
        }

        $cropped = imagecrop($image, [
            'x' => (int) (($width - $croppedWidth) / 2),
            'y' => (int) (($height - $croppedHeight) / 2),
            'width' => $croppedWidth,
            'height' => $croppedHeight,
        ]);

        return $cropped === false ? $image : $cropped;
    }

    private function scaleTo(\GdImage $image, int $width): \GdImage
    {
        $scaled = imagescale($image, $width);

        return $scaled === false ? $image : $scaled;
    }

    private function store(\GdImage $image, string $path): void
    {
        ob_start();
        imagewebp($image, null, self::QUALITY);
        $bytes = (string) ob_get_clean();

        Storage::disk('public')->put($path, $bytes);
    }
}
