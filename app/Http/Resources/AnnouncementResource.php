<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AnnouncementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $last_update_datetime = $this->updated_at ?? $this->created_at;
        $dateLastUpdate = $last_update_datetime
            ? Carbon::parse($last_update_datetime)->format('d-m-Y H:i') . ' WIB'
            : '-';

        $tgl_berlaku = $this->tgl_berlaku
            ? Carbon::parse($this->tgl_berlaku)->format('Y-m-d')
            : null;

        $tgl_berlaku_formatted = $this->tgl_berlaku
            ? Carbon::parse($this->tgl_berlaku)->format('d-m-Y')
            : '-';

        $tgl_dibuka = $this->created_at
            ? Carbon::parse($this->created_at)->format('d-m-Y H:i') . ' WIB'
            : null;

        return [
            'id' => $this->id,
            'submenu_id' => $this->submenu_id,
            'title' => $this->title,
            'no_surat' => $this->no_surat,
            'url' => $this->url,
            'tgl_berlaku' => $tgl_berlaku,
            'tgl_berlaku_formatted' => $tgl_berlaku_formatted,
            'tgl_dibuka' => $tgl_dibuka,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'content' => $this->content,
            'type' => $this->type,
            'dateLastUpdate' => $dateLastUpdate,
            'content_url' => $this->url ? (config('services.mix.url') . $this->url) : null,
            'menu' => $this->whenLoaded('menu'),
            'document_position' => $this->whenLoaded('document_position'),
            'document_regional' => $this->whenLoaded('document_regional'),
        ];
    }
}
