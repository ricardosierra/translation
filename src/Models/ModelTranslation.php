<?php

namespace Translation\Models;

use Illuminate\Database\Eloquent\Model;
use Translation\Traits\HasTranslationsTrait;

class ModelTranslation extends Model
{
    public $table = 'model_translations';

    public $primaryKey = 'id';

    protected $guarded = [];

    public $rules = [];

    protected $fillable = [
        // 'item',
        // 'group',
        // 'text',
        // 'locale',

        'entity_id',
        'entity_type',
        'entity_data',
        'country_code',
        'language_code',
    ];

    public function getDataAttribute()
    {
        $object = app($this->entity_type);

        $attributes = (array) json_decode($this->text);
        $object->attributes = array_merge(
            $attributes, [
            $object->getKey() => $this->item,
            ]
        );

        return $object;
    }
}
