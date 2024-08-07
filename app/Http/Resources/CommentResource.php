<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            /*'commentable' => [
                'type' => $this->commentable_type,
                'id' => $this->commentable_id,
            ],*/
            'commentable' => $this->whenLoaded('commentable'),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->format('j.n.Y H:i'),
            'updated_at' => $this->updated_at->format('j.n.Y H:i'),
        ];
    }
    
}