<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'required|in:admin,employee,client,livreur',
            'is_active' => 'boolean'
        ];

        if ($this->isMethod('post')) {
            $rules['email'] = 'required|string|email|max:255|unique:users';
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $this->route('user');
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }
}
