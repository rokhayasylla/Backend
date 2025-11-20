<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = auth()->user();
        return [
            'message' => [
                'required',
                'string',
                'max:1000',
                'min:1'
            ],
            // Ajouter ces règles pour le support
            'user_id' => $user->role === 'client'
                ? 'prohibited'
                : 'required|integer|exists:users,id',
            'sender_type' => 'nullable|in:client,support'
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Le message est obligatoire.',
            'message.string' => 'Le message doit être une chaîne de caractères.',
            'message.max' => 'Le message ne peut pas dépasser 1000 caractères.',
            'message.min' => 'Le message ne peut pas être vide.',
            'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
            'sender_type.in' => 'Le type d\'expéditeur doit être client ou support.'
        ];
    }
}
