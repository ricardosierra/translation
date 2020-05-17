<?php

namespace RicardoSierra\Translation\Models;

use Illuminate\Database\Eloquent\Model;
use RicardoSierra\Translation\Traits\TranslationTrait;

class Translation extends Model
{
    use TranslationTrait;

    /**
     *  Table name in the database.
     *
     * @var string
     */
    protected $table = 'model_translations';

    /**
     *  List of variables that can be mass assigned
     *
     * @var array
     */
    protected $fillable = [
        'locale', 'namespace', 'group', 'item', 'text', 'unstable',
        'translation_id',
        'translation',
    ];

    /**
     *  Each translation belongs to a language.
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }

    /**
     *  Returns the full translation code for an entry: namespace.group.item
     *
     * @return string
     */
    public function getCodeAttribute()
    {
        return $this->namespace === '*' ? "{$this->group}.{$this->item}" : "{$this->namespace}::{$this->group}.{$this->item}";
    }

    /**
     *  Flag this entry as Reviewed
     *
     * @return void
     */
    public function flagAsReviewed()
    {
        $this->unstable = 0;
    }

    /**
     *  Set the translation to the locked state
     *
     * @return void
     */
    public function lock()
    {
        $this->locked = 1;
    }

    /**
     *  Check if the translation is locked
     *
     * @return boolean
     */
    public function isLocked()
    {
        return (boolean) $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function locale()
    {
        return $this->belongsTo(Locale::class, 'locale', 'code');
    }

    /**
     * {@inheritdoc}
     */
    public function parent()
    {
        return $this->belongsTo(self::class, $this->getForeignKey());
    }
}
/**
 * <?php

namespace Informate\Models\System;

use Informate\Traits\ArchiveTrait;
 *
 * @todo Verificar compatibilidade com 
 * \RicardoSierra\Translation\Models\System\Translation
 * 
 * Aqui é para Campos do Modelo e la nao 

class Translation extends ArchiveTrait
{
    public $table = 'model_translations';

    public $primaryKey = 'id';

    protected $guarded = [];

    public $rules = [];

    protected $fillable = [
        'item',
        'group',
        'text',
        'locale',

        // 'entity_id',
        // 'entity_type',
        // 'entity_data',
        // 'language',
    ];

    public function getDataAttribute()
    {
        $object = app($this->entity_type);

        $attributes = (array) json_decode($this->text);
        $object->attributes = array_merge($attributes, [
            'id' => $this->item,
        ]);

        return $object;
    }
}
 */