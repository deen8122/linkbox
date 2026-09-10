<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkBlockGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'color' => [
                'nullable',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],
            'background_color' => [
                'nullable',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],
        ];
    }
}
