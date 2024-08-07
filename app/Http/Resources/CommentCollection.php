<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CommentCollection extends ResourceCollection
{
    public function toArray(Request $request)
    {
        return [
            'data' => CommentResource::collection($this->collection),
        ];
    }
}
