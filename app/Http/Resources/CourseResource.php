<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail ? (config('services.mix.img_url') . ltrim($this->thumbnail, '/')) : null,
            'category_id' => $this->category_id,
            'is_popular' => $this->is_popular,
            'students' => $this->students,
            'details' => $this->details,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category'),
            'contents' => $this->whenLoaded('contents'),
        ];
    }
}
