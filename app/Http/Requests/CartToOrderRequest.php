<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartToOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_address' => 'required|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,online',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_address.required' => 'L\'adresse de livraison est requise',
            'delivery_address.string' => 'L\'adresse de livraison doit être une chaîne de caractères',
            'delivery_address.max' => 'L\'adresse de livraison ne peut pas dépasser 255 caractères',
            'payment_method.required' => 'La méthode de paiement est requise',
            'payment_method.in' => 'La méthode de paiement doit être cash_on_delivery ou online',

        ];
    }
}
