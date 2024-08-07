<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'content' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'commentable_id' => 'required',
            'commentable_type' => 'required|string',
        ];
    }
}
