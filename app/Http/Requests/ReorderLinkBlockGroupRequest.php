<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLinkBlockGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'groups' => [
                'required',
                'array',
            ],

            'groups.*.id' => [
                'required',
                'integer',
            ],

            'groups.*.position' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
