<?php

namespace RicardoSierra\Translation\Traits;

use RicardoSierra\Translation\Models\Translation;
use RicardoSierra\Translation\Repositories\TranslationRepository;

class TranslatableObserver
{
    /**
     *  Save translations when model is saved.
     *
     * @param  Model $model
     * @return void
     */
    public function saved($model)
    {
        $translationRepository = \App::make(TranslationRepository::class);
        $cacheRepository       = \App::make('translation.cache.repository');
        foreach ($model->translatableAttributes() as $attribute) {
            // If the value of the translatable attribute has changed:
            if ($model->isDirty($attribute)) {
                $translationRepository->updateDefaultByCode($model->translationCodeFor($attribute), $model->getRawAttribute($attribute));
            }
        }
        $cacheRepository->flush(\Illuminate\Support\Facades\Config::get('app.locale'), 'translatable', '*');
    }

    /**
     *  Delete translations when model is deleted.
     *
     * @param  Model $model
     * @return void
     */
    public function deleted($model)
    {
        $translationRepository = \App::make(TranslationRepository::class);
        foreach ($model->translatableAttributes() as $attribute) {
            $translationRepository->deleteByCode($model->translationCodeFor($attribute));
        }
    }
}
