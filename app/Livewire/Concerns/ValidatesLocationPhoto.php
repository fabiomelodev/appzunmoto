<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Shared handling of the optional "foto do local" upload (a public $photo property).
 *
 * Only formats a browser can actually draw are accepted: Livewire builds the preview
 * from a signed URL and throws for anything outside its previewable list (HEIC from an
 * iPhone, AVIF, JFIF…), which used to blow up the request and leave the picker stuck on
 * "Enviando…". A rejected file is now dropped at once and the user is told why.
 */
trait ValidatesLocationPhoto
{
    /** @return array<int, string> */
    protected function photoRules(bool $nullable = false): array
    {
        return array_merge($nullable ? ['nullable'] : [], ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096']);
    }

    /** @return array<string, string> */
    protected function photoMessages(): array
    {
        return [
            'photo.image' => 'Formato não suportado. Envie uma foto JPG, PNG ou WebP.',
            'photo.mimes' => 'Formato não suportado. Envie uma foto JPG, PNG ou WebP (fotos HEIC do iPhone não funcionam).',
            'photo.max' => 'A foto pode ter no máximo 4 MB.',
            'photo.uploaded' => 'Não foi possível enviar a foto. Tente novamente.',
        ];
    }

    /** Validates the fresh upload; on failure drops it (so no broken preview is rendered) and rethrows. */
    protected function validatePhotoUpload(): void
    {
        try {
            $this->validate(['photo' => $this->photoRules()], $this->photoMessages());
        } catch (ValidationException $e) {
            $this->photo = null;

            throw $e;
        }
    }
}
