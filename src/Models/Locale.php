<?php

namespace RicardoSierra\Translation\Models;

use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    
    public $incrementing = false;
    protected $casts = [
        'code' => 'string',
    ];
    protected $primaryKey = 'code';
    protected $keyType = 'string';

    /**
     * The locales table.
     *
     * @var string
     */
    protected $table = 'locales';

    /**
     * The fillable locale attributes.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'lang_code',
        'name',
        'display_name',
    ];

    // /**
    //  * {@inheritdoc].
    //  */
    // public function translations()
    // {
    //     return $this->hasMany(Translation::class); // @todo usar https://github.com/topclaudy/compoships
    // }
    
}
