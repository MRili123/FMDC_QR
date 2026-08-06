<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\DraftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(private DraftService $draft) {}

    public function store(Request $request, string $locale)
    {
        $request->validate([
            'fichier' => [
                'required', 'file',
                'max:'.config('qrconso.uploads.max_size_kb'),
                'mimes:jpg,jpeg,png,webp,heic,pdf,mp3,m4a,ogg,wav,webm',
            ],
            'kind' => ['required', 'in:'.implode(',', config('taxonomy.attachment_kinds'))],
        ]);

        $draftId = $this->draft->draftId();

        $count = Attachment::where('draft_id', $draftId)->count();
        if ($count >= config('qrconso.uploads.max_per_draft')) {
            return back()->withErrors(['fichier' => __('wizard.step4.tooMany')]);
        }

        $file = $request->file('fichier');
        $path = $file->store('pieces', 'local');

        Attachment::create([
            'draft_id' => $draftId,
            'kind' => $request->string('kind'),
            'storage_key' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            // Pas d'antivirus en développement ; le §9 en prévoit un en production.
            'scan_status' => 'SKIPPED',
        ]);

        return back();
    }

    public function destroy(string $locale, Attachment $attachment)
    {
        // On ne peut supprimer qu'une pièce de son propre brouillon, et jamais
        // une pièce déjà rattachée à un dossier déposé.
        abort_unless(
            $attachment->draft_id === $this->draft->draftId() && $attachment->dossier_id === null,
            403,
        );

        Storage::disk('local')->delete($attachment->storage_key);
        $attachment->delete();

        return back();
    }
}
