<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isEmployee());
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
            'is_active' => 'boolean',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1'
        ];

        if ($this->isMethod('post')) {
            // Lors de la création, l'image est OBLIGATOIRE
            $rules['image_path'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            // Lors de la mise à jour, l'image est OPTIONNELLE
            $rules['image_path'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
            $rules['name'] = 'nullable|string|max:255';
            $rules['price'] = 'nullable|numeric|min:0';
            $rules['products'] = 'nullable|array|min:1';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'image.required' => 'L\'image est obligatoire lors de la création d\'un pack.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format: jpeg, png, jpg, gif.',
            'image.max' => 'L\'image ne doit pas dépasser 2MB.',
        ];
    }
}
