<?php

namespace Tests\Unit;

use App\Enums\VideoProvider;
use App\Support\VideoUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, VideoProvider, string}>
     */
    public static function validUrls(): array
    {
        return [
            'vimeo id' => ['563689407', VideoProvider::Vimeo, '563689407'],
            'vimeo page' => ['https://vimeo.com/563689407', VideoProvider::Vimeo, '563689407'],
            'vimeo player' => ['https://player.vimeo.com/video/563689407?title=0&byline=0', VideoProvider::Vimeo, '563689407'],
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', VideoProvider::Youtube, 'dQw4w9WgXcQ'],
            'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ', VideoProvider::Youtube, 'dQw4w9WgXcQ'],
            'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', VideoProvider::Youtube, 'dQw4w9WgXcQ'],
            'panda embed' => [
                'https://player-vz-abc123.tv.pandavideo.com.br/embed/?v=0b1c2d3e',
                VideoProvider::Panda,
                'https://player-vz-abc123.tv.pandavideo.com.br/embed/?v=0b1c2d3e',
            ],
        ];
    }

    #[DataProvider('validUrls')]
    public function test_it_recognizes_supported_links(string $input, VideoProvider $provider, string $id): void
    {
        $this->assertSame(['provider' => $provider, 'id' => $id], VideoUrl::parse($input));
    }

    public function test_it_rejects_unknown_links(): void
    {
        $this->assertNull(VideoUrl::parse('https://example.com/video.mp4'));
        $this->assertNull(VideoUrl::parse(''));
        $this->assertNull(VideoUrl::parse(null));
    }

    public function test_it_builds_a_shareable_url_back(): void
    {
        $this->assertSame('https://vimeo.com/563689407', VideoUrl::toUrl(VideoProvider::Vimeo, '563689407'));
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', VideoUrl::toUrl(VideoProvider::Youtube, 'dQw4w9WgXcQ'));
        $this->assertNull(VideoUrl::toUrl(null, null));
    }
}
