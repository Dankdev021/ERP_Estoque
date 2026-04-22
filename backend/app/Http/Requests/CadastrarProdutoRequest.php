<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CadastrarProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3'],
            'preco_venda' => ['required', 'numeric', 'gt:0'],
            'estoque_inicial' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'preco_venda' => 'preço de venda',
            'estoque_inicial' => 'estoque inicial',
        ];
    }
}
