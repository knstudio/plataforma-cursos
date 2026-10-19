<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp funnel module (formerly n8n + its own MySQL database).
 *
 * The platform owns the sales: blocks point at products, messages at product
 * offers, and "already bought" is read from approved orders. The module owns
 * the conversations: contacts, funnels, their entry triggers and where each
 * contact is in each funnel (one enrollment per contact and funnel).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A strategy with its own WhatsApp instance and sending hours. When a
        // contact is in several funnels, only one sends at a time: post-sale
        // funnels first, then the highest prioridade.
        Schema::create('funil_funis', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nome', 120);
            $table->string('instancia', 60);
            $table->integer('prioridade')->default(0);
            $table->unsignedTinyInteger('hora_inicio_envio')->default(8);
            $table->unsignedTinyInteger('hora_fim_envio')->default(21);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // How contacts enter a funnel. tipo: formulario | compra_produto (later
        // palavra_chave, manual). product_id is used by compra_produto; settings
        // specific to a type live in config (formulario: pagina_obrigado,
        // link_ebook, msg_confirmacao, msg_ebook).
        Schema::create('funil_gatilhos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funil_id')->constrained('funil_funis')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('config')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'ativo']);
        });

        // The funnel queue: one product of the platform per block.
        // espera_inicio_min: wait after the previous block ends (first block: after entry).
        // espera_se_comprou_min: wait when the previous block's product was bought (null = espera_inicio_min).
        Schema::create('funil_blocos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funil_id')->constrained('funil_funis')->cascadeOnDelete();
            $table->unsignedInteger('posicao');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('espera_inicio_min')->default(60);
            $table->unsignedInteger('espera_se_comprou_min')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['funil_id', 'posicao']);
        });

        // espera_min counts from the previous message (the first one, from the block start).
        // Texts accept {primeiro_nome} {produto} {preco} {link}; preco and link come from the offer.
        Schema::create('funil_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloco_id')->constrained('funil_blocos')->cascadeOnDelete();
            $table->unsignedInteger('ordem');
            $table->unsignedInteger('espera_min')->default(0);
            $table->foreignId('product_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('texto');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['bloco_id', 'ordem']);
        });

        // One person, one record, shared by every funnel. descadastrado_em (SAIR)
        // takes the contact out of all funnels for good.
        Schema::create('funil_contatos', function (Blueprint $table) {
            $table->id();
            $table->string('telefone', 20)->unique();
            $table->string('nome', 150)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('ultima_interacao_em')->nullable();
            $table->timestamp('descadastrado_em')->nullable();
            $table->timestamps();
        });

        // Contact X in funnel Y, at block Z.
        // status: aguardando_confirmacao | em_espera (another funnel is sending) | no_funil | concluido | descadastrado
        // Pointer: bloco_atual_id + ultima_ordem_enviada (0 = nothing sent in the block yet);
        // proximo_envio_em = when the dispatcher acts again.
        Schema::create('funil_inscricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contato_id')->constrained('funil_contatos')->cascadeOnDelete();
            $table->foreignId('funil_id')->constrained('funil_funis')->cascadeOnDelete();
            $table->foreignId('gatilho_id')->nullable()->constrained('funil_gatilhos')->nullOnDelete();
            $table->foreignId('origem_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('status', 30)->default('aguardando_confirmacao');
            $table->foreignId('bloco_atual_id')->nullable()->constrained('funil_blocos')->nullOnDelete();
            $table->unsignedInteger('ultima_ordem_enviada')->default(0);
            $table->timestamp('proximo_envio_em')->nullable();
            $table->timestamp('processando_desde')->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();

            $table->unique(['contato_id', 'funil_id']);
            $table->index(['status', 'proximo_envio_em']);
        });

        // The same message never goes twice to the same enrollment.
        Schema::create('funil_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('funil_inscricoes')->cascadeOnDelete();
            $table->foreignId('bloco_id')->nullable()->constrained('funil_blocos')->nullOnDelete();
            $table->foreignId('mensagem_id')->nullable()->constrained('funil_mensagens')->nullOnDelete();
            $table->foreignId('product_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->string('detalhe')->nullable();
            $table->timestamps();

            $table->unique(['inscricao_id', 'mensagem_id']);
        });

        // Incoming WhatsApp/form messages already handled (de-duplication).
        Schema::create('funil_msg_processadas', function (Blueprint $table) {
            $table->string('message_id', 100)->primary();
            $table->string('telefone', 20);
            $table->timestamp('recebida_em')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funil_msg_processadas');
        Schema::dropIfExists('funil_envios');
        Schema::dropIfExists('funil_inscricoes');
        Schema::dropIfExists('funil_contatos');
        Schema::dropIfExists('funil_mensagens');
        Schema::dropIfExists('funil_blocos');
        Schema::dropIfExists('funil_gatilhos');
        Schema::dropIfExists('funil_funis');
    }
};
