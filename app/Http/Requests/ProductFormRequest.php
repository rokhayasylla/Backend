<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //return auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isEmployee());
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'allergens' => 'nullable|string',
            'category_id' => 'required|exists:categories,id'
        ];

        if ($this->isMethod('post')) {
            // Lors de la création, l'image est optionnelle
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048'; // 2MB max
        } else {
            // Lors de la mise à jour, l'image est optionnelle
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format: jpeg, png, jpg, gif.',
            'image.max' => 'L\'image ne doit pas dépasser 2MB.',
        ];
    }
}
