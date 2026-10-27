<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormDownloadTest extends TestCase
{
    public function test_form_files_download_as_attachments_while_previews_remain_inline(): void
    {
        $disk = Storage::fake('public');
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'employee', 'idgrup' => 'OTHER']]);
        foreach (['pdf', 'docx', 'xlsx'] as $extension) {
            $name = 'form 01.'.$extension;
            $disk->put('aktif/FORM PP/'.$name, 'original file bytes');
            $url = '/storage/aktif/FORM%20PP/'.rawurlencode($name);
            $this->get($url.'?download=1')->assertOk()
                ->assertHeader('Content-Disposition', 'attachment; filename="'.$name.'"')
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertStreamedContent('original file bytes');
            $this->get($url)->assertOk()
                ->assertHeader('Content-Disposition', 'inline; filename="'.$name.'"');
        }
    }

    public function test_downloads_keep_session_restrictions_and_missing_file_response(): void
    {
        Storage::fake('public')->put('aktif/FORM PP/test.pdf', 'private');
        $url = '/storage/aktif/FORM%20PP/test.pdf?download=1';
        $this->get($url)->assertRedirect('/sign-in');
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'audit', 'idgrup' => 'JBT-020']])
            ->get($url)->assertForbidden();
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'employee', 'idgrup' => 'OTHER']])
            ->get('/storage/aktif/FORM%20PP/missing.pdf?download=1')->assertNotFound();
    }
}
