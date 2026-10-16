<?php

namespace App\Enums;

enum VideoProvider: string
{
    case Vimeo = 'vimeo';
    case Youtube = 'youtube';
    case Panda = 'panda';

    public function label(): string
    {
        return match ($this) {
            self::Vimeo => 'Vimeo',
            self::Youtube => 'YouTube',
            self::Panda => 'Panda Video',
        };
    }

    /**
     * Build the iframe embed URL for the given video identifier.
     */
    public function embedUrl(string $videoId): string
    {
        return match ($this) {
            self::Vimeo => "https://player.vimeo.com/video/{$videoId}?title=0&byline=0&portrait=0",
            self::Youtube => "https://www.youtube.com/embed/{$videoId}?rel=0&modestbranding=1",
            self::Panda => $videoId,
        };
    }
}
