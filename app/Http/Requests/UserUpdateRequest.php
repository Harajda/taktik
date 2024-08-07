<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    protected $userId; // Tieto musia byť prístupné, ak chceme dynamicky meniť pravidlá

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $this->userId,
            'password' => 'sometimes|required|string|min:8'
        ];
    }

    public function setUserId($id)
    {
        $this->userId = $id;
    }
}
