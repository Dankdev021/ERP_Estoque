<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor' => ['required', 'string', 'min:2', 'max:255'],
            'produtos' => ['required', 'array', 'min:1'],
            'produtos.*.id' => ['required', 'integer', 'exists:products,id'],
            'produtos.*.quantidade' => ['required', 'integer', 'min:1'],
            'produtos.*.preco_unitario' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return [
            'fornecedor' => 'fornecedor',
            'produtos' => 'produtos',
            'produtos.*.id' => 'produto',
            'produtos.*.quantidade' => 'quantidade',
            'produtos.*.preco_unitario' => 'preço unitário',
        ];
    }
}
