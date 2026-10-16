<?php

namespace Database\Seeders;

use App\Enums\VideoProvider;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds "Arte com Balões Básico", the course that used to live as static
 * PHP pages in alunos-juliana-baloes, so the platform starts with real content.
 */
class CourseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $course = Course::query()->updateOrCreate(
            ['slug' => 'arte-com-baloes-basico'],
            [
                'title' => 'Arte com Balões Básico',
                'short_description' => 'Treinamento essencial para quem está começando na arte com balões.',
                'description' => 'Um treinamento pra você que está iniciando ou pretende aprimorar seus conhecimentos. '
                    .'Do absoluto zero até guirlandas, arcos, esculturas e painéis.',
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        if ($course->modules()->exists()) {
            return;
        }

        $modules = [
            'Treinamento' => [
                ['Introdução', '536759157', 151, '<p><strong>Seja bem vindo(a) a PRIMEIRA aula do treinamento ARTE COM BALÕES BÁSICO!!</strong></p><p>Um treinamento pra você que está iniciando ou pretende aprimorar seus conhecimentos. Irei mostrar o caminho certo, os primeiros passos, e como prosseguir até alcançar o conhecimento para você fazer maravilhas em artes com balões desde o absoluto zero.</p>'],
                ['Materiais Necessários', '563686511', 346, '<p>Nessa aula mostro quais os materiais necessários para trabalhar com balões. É uma dúvida comum de muitas pessoas que estão iniciando. Conhecer os materiais básicos é essencial para criar arte com balões de qualidade.</p>'],
                ['Técnicas Básicas', '563689407', 361, '<p>Nessa aula mostro as técnicas. Técnica é o princípio de tudo. Para se fazer esculturas, murais, painéis, enfim, tudo tem técnica quando falamos de arte com balões.</p>'],
                ['Guirlandas', '563692125', 583, '<p>Na aula passada falei sobre clusters e mostrei que através deles é possível fazer guirlandas. Nessa aula vou mostrar exemplos de guirlandas e o que pode ser feito com elas.</p>'],
                ['Arcos de Balões', '563694298', 565, '<p>Na aula passada falei sobre guirlandas e mostrei que através delas é possível fazer uma variedade de arcos. Agora vou mostrar exemplos de arcos e como fazer.</p>'],
                ['Esculturas de Balões', '536775124', 414, '<p>Essa aula é sobre esculturas.</p>'],
                ['Painéis de Balões', '536775505', 440, '<p>Essa aula é sobre painéis de balões.</p>'],
            ],
            'Bônus' => [
                ['Árvore Chapada', '566571014', null, null],
                ['Max Buquet Orgânico', '566563477', null, null],
                ['Arranjo de Chão com Urso', '671355653', null, null],
            ],
        ];

        $moduleOrder = 0;

        foreach ($modules as $moduleTitle => $lessons) {
            $module = $course->modules()->create([
                'title' => $moduleTitle,
                'sort_order' => ++$moduleOrder,
            ]);

            foreach ($lessons as $lessonOrder => [$title, $videoId, $duration, $content]) {
                $module->lessons()->create([
                    'title' => $title,
                    'video_provider' => VideoProvider::Vimeo,
                    'video_id' => $videoId,
                    'duration_seconds' => $duration,
                    'content' => $content,
                    'sort_order' => $lessonOrder + 1,
                ]);
            }
        }

        $product = Product::query()->updateOrCreate(
            ['slug' => 'treinamento-essencial'],
            [
                'name' => 'Treinamento Essencial Arte com Balões',
                'price' => 17.00,
                'description' => 'Treinamento Essencial + Apostila + Vídeos + Bônus',
                'is_active' => true,
            ],
        );

        $product->courses()->syncWithoutDetaching([$course->id]);
    }
}
