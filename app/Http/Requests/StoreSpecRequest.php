<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'budget' => ['required', 'integer', 'min:0'],
            'room_type' => ['required', 'string'],
            'occupants' => ['required', 'integer', 'min:1'],
            'cooking' => ['required', 'in:never,sometimes,often'],
            'laundry' => ['required', 'in:own_machine,hand,service'],
            'work_style' => ['required', 'in:home,office,hybrid'],
            'spending_style' => ['required', 'in:essentials,balanced,comfort'],
        ];
    }
}
