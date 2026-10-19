<?php

namespace Database\Seeders;

use App\Enums\VideoProvider;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Seeds "Pac Esculturas com Balões" (the old order-bump-pac-esculturas pages)
 * and offers it as the order bump of the Treinamento Essencial checkout.
 */
class PacEsculturasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $course = Course::query()->updateOrCreate(
            ['slug' => 'pac-esculturas-com-baloes'],
            [
                'title' => 'Pac Esculturas com Balões',
                'short_description' => '22 vídeos de esculturas com balões, do sol ao arco-íris.',
                'description' => '<p>Pacote com 22 vídeos de esculturas e técnicas com balões: sol, nuvem, árvore, borboleta, arcos orgânicos, personagens e muito mais.</p>',
                'is_published' => true,
                'sort_order' => 2,
            ],
        );

        if (! $course->modules()->exists()) {
            $module = $course->modules()->create(['title' => 'Esculturas com Balões', 'sort_order' => 1]);

            foreach ($this->lessons() as $index => [$title, $videoId, $duration, $content]) {
                $module->lessons()->create([
                    'title' => $title,
                    'video_provider' => VideoProvider::Vimeo,
                    'video_id' => $videoId,
                    'duration_seconds' => $duration,
                    'content' => "<p>{$content}</p>",
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $course->modules()->firstOrFail()->lessons()->firstOrCreate(
            ['title' => 'Grupo WhatsApp'],
            [
                'content' => '<p>Participe do grupo de alunos no WhatsApp para tirar dúvidas e trocar experiências:</p>'
                    .'<p><a href="https://chat.whatsapp.com/BX6ebetmRQe7y12Vo2AiGA" target="_blank" rel="noopener">'
                    .'<strong>Clique aqui para entrar no grupo</strong></a></p>',
                'sort_order' => 0,
            ],
        );

        $product = Product::query()->updateOrCreate(
            ['slug' => 'pac-esculturas-com-baloes'],
            [
                'name' => 'Pac Esculturas com Balões',
                'description' => '+21 vídeos de esculturas com balões',
                'price' => 105.00,
                'is_active' => true,
            ],
        );
        $product->courses()->syncWithoutDetaching([$course->id]);

        Product::query()
            ->where('slug', 'treinamento-essencial')
            ->first()
            ?->orderBumps()
            ->firstOrCreate(['bump_product_id' => $product->id], [
                'price' => 12.00,
                'headline' => 'Quero aproveitar essa *OPORTUNIDADE ÚNICA* com desconto de 83% e levar mais 21 vídeos de Esculturas com Balões.',
                'sort_order' => 0,
            ]);
    }

    /**
     * Title, Vimeo ID, duration in seconds and description, in the original order.
     *
     * @return list<array{0: string, 1: string, 2: int, 3: string}>
     */
    private function lessons(): array
    {
        return [
            ['Sol de Balões', '605600970', 442, 'Nesse vídeo você vai aprender a técnica para fazer sol de balões.'],
            ['Nuvem de Balões', '605600057', 225, 'Nesse vídeo você vai aprender a técnica para fazer nuvem de balões.'],
            ['Árvore Básica', '605587210', 343, 'Nesse vídeo você vai aprender os conceitos básicos para fazer uma árvore de balões.'],
            ['Borboleta', '605590973', 471, 'Aprenda a fazer uma linda borboleta de balões.'],
            ['Boneco de Neve', '605589921', 384, 'Aprenda a fazer um lindo boneco de neve.'],
            ['Buquê de Balões', '605593453', 153, 'Aprenda a fazer um lindo buquê de balões para usar em arranjos de mesa.'],
            ['Cacho de Uvas', '605596732', 260, 'Aprenda a fazer uma escultura cacho de uvas de balões para usar em temas de primeira comunhão, etc.'],
            ['Coração de Balão 260', '605597070', 106, 'Nesse vídeo você vai aprender como fazer um coração com balão 260.'],
            ['Escultura Elefante', '605597411', 223, 'Nesse vídeo você vai aprender a fazer uma escultura de elefante com telas.'],
            ['Arco Orgânico', '605585123', 751, 'Aprenda as técnicas usadas para fazer arcos orgânicos e faça você também esse lindo arco desconstruído.'],
            ['Flor Distorção', '605599844', 512, 'Nesse vídeo você vai aprender a fazer uma linda flor com a técnica de distorção.'],
            ['Joaninha', '605599930', 271, 'Aprenda como fazer uma escultura joaninha para usar em temas como jardim.'],
            ['Papai Noel', '605600134', 452, 'Nesse vídeo você vai aprender a fazer um lindo Papai Noel de balões.'],
            ['Peixe com Telas', '605600244', 393, 'Aprenda a técnica para fazer uma escultura de peixe com telas.'],
            ['Peppa Pig', '605600312', 749, 'Aprenda a fazer a escultura da Peppa Pig.'],
            ['Semáforo', '605600824', 220, 'Nesse vídeo você vai aprender a fazer um lindo semáforo.'],
            ['Pinheiro', '605600442', 225, 'Aprenda a fazer um pinheiro de balões.'],
            ['Arco Orgânico 02', '605581878', 319, 'Aprenda a fazer esse outro lindo arco orgânico.'],
            ['Balão Customizado', '605588814', 265, 'Nesse vídeo você vai aprender a customizar os balões e criar novas cores.'],
            ['Balão 260', '605596837', 299, 'Aprenda a trabalhar com balão 260.'],
            ['Estouro Mágico', '605597551', 211, 'Nesse vídeo eu vou revelar a técnica do estouro mágico, que pode ser usada em chás de revelação.'],
            ['Arco-Íris', '775800143', 624, 'Nesse vídeo você vai aprender a técnica para fazer um lindo arco-íris de balões.'],
        ];
    }
}
