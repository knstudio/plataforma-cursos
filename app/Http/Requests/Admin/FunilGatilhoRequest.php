<?php

namespace App\Http\Requests\Admin;

use App\Enums\FunilGatilhoTipo;
use App\Models\Funil;
use App\Models\FunilGatilho;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FunilGatilhoRequest extends FormRequest
{
    /**
     * Kept apart from the funnel form errors shown on the same page.
     *
     * @var string
     */
    protected $errorBag = 'gatilho';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $formulario = 'required_if:tipo,'.FunilGatilhoTipo::Formulario->value;

        return [
            'tipo' => ['required', Rule::enum(FunilGatilhoTipo::class)],
            'product_id' => ['nullable', 'required_if:tipo,'.FunilGatilhoTipo::CompraProduto->value, 'integer', 'exists:products,id'],
            'pagina_obrigado' => ['nullable', $formulario, 'url', 'max:255'],
            'link_ebook' => ['nullable', $formulario, 'url', 'max:255'],
            'msg_confirmacao' => ['nullable', $formulario, 'string', 'max:4000'],
            'msg_ebook' => ['nullable', $formulario, 'string', 'max:4000'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * One form per funnel (the landing posts only the funnel slug) and one entry per product.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $tipo = FunilGatilhoTipo::from($this->input('tipo'));

                $repetido = FunilGatilho::query()
                    ->where('funil_id', $this->funil()->id)
                    ->where('tipo', $tipo)
                    ->when($tipo === FunilGatilhoTipo::CompraProduto, fn ($query) => $query->where('product_id', $this->input('product_id')))
                    ->when($this->route('gatilho'), fn ($query, FunilGatilho $gatilho) => $query->whereKeyNot($gatilho->id))
                    ->exists();

                if ($repetido) {
                    $validator->errors()->add('tipo', $tipo === FunilGatilhoTipo::Formulario
                        ? 'Este funil já tem um gatilho de formulário.'
                        : 'Este funil já tem um gatilho para a compra deste produto.');
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForSave(): array
    {
        $tipo = FunilGatilhoTipo::from($this->validated('tipo'));

        return [
            'tipo' => $tipo,
            'product_id' => $tipo === FunilGatilhoTipo::CompraProduto ? $this->validated('product_id') : null,
            'config' => $tipo === FunilGatilhoTipo::Formulario
                ? $this->safe()->only(['pagina_obrigado', 'link_ebook', 'msg_confirmacao', 'msg_ebook'])
                : null,
            'ativo' => $this->validated('ativo'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produto',
            'pagina_obrigado' => 'página de obrigado',
            'link_ebook' => 'link do ebook',
            'msg_confirmacao' => 'mensagem de confirmação',
            'msg_ebook' => 'mensagem com o ebook',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required_if' => 'Escolha o produto cuja compra inicia o funil.',
            '*.required_if' => 'Preencha :attribute (obrigatório para o formulário).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    private function funil(): Funil
    {
        $funil = $this->route('funil');

        return $funil instanceof Funil ? $funil : $this->route('gatilho')->funil;
    }
}
