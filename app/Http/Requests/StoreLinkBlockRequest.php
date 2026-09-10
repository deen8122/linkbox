<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'max:2048',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'link_block_group_id' => [
                'nullable',
                'integer',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:15360',
            ],
        ];
    }
}
