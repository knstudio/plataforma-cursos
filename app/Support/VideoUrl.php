<?php

namespace App\Support;

use App\Enums\VideoProvider;

/**
 * Turns whatever the admin pastes (watch URL, embed URL or bare Vimeo ID)
 * into a provider + identifier pair, and back into a friendly URL for forms.
 */
class VideoUrl
{
    /**
     * @return array{provider: VideoProvider, id: string}|null
     */
    public static function parse(?string $input): ?array
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if (preg_match('/^\d{6,}$/', $input)) {
            return ['provider' => VideoProvider::Vimeo, 'id' => $input];
        }

        if (preg_match('#vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d{6,})#i', $input, $matches)) {
            return ['provider' => VideoProvider::Vimeo, 'id' => $matches[1]];
        }

        if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $input, $matches)) {
            return ['provider' => VideoProvider::Youtube, 'id' => $matches[1]];
        }

        if (preg_match('#^https://[^/\s]*pandavideo\.com\.br/\S+$#i', $input)) {
            return ['provider' => VideoProvider::Panda, 'id' => $input];
        }

        return null;
    }

    public static function toUrl(?VideoProvider $provider, ?string $videoId): ?string
    {
        if ($provider === null || blank($videoId)) {
            return null;
        }

        return match ($provider) {
            VideoProvider::Vimeo => "https://vimeo.com/{$videoId}",
            VideoProvider::Youtube => "https://www.youtube.com/watch?v={$videoId}",
            VideoProvider::Panda => $videoId,
        };
    }
}
