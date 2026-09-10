<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLinkBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blocks' => [
                'required',
                'array',
            ],

            'blocks.*.id' => [
                'required',
                'integer',
            ],

            'blocks.*.position' => [
                'required',
                'integer',
                'min:0',
            ],

            'blocks.*.link_block_group_id' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
