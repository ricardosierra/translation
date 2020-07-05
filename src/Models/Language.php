<?php

namespace RicardoSierra\Translation\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     *  Table name in the database.
     *
     * @var string
     */
    protected $table = 'languages';
    
    public $incrementing = false;
    protected $casts = [
        'code' => 'string',
    ];
    protected $primaryKey = 'code';
    protected $keyType = 'string';

    /**
     *  List of variables that cannot be mass assigned
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     *  Each language may have several translations.
     * 
     * SOmente se nao tiver pais
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }

    /**
     *  Each language may have several translations.
     */
    public function locales()
    {
        return $this->hasMany(Locale::class, 'language_code', 'code');
    }

    /**
     *  Returns the name of this language in the current selected language.
     *
     * @return string
     */
    public function getLanguageCodeAttribute()
    {
        return "languages.{$this->language_code}";
    }
    
    public function getImageUrl( $withBaseUrl = false )
    {
        if(!$this->icon) { return null;
        }
        
        $imgDir = '/images/languages/' . $this->id;
        $url = $imgDir . '/' . $this->icon;
        
        return $withBaseUrl ? URL::asset($url) : $url;
    }

}
