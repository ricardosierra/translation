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
    protected $fillable = ['locale', 'name'];

    /**
     *  Each language may have several translations.
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'locale', 'locale');
    }

    /**
     *  Returns the name of this language in the current selected language.
     *
     * @return string
     */
    public function getLanguageCodeAttribute()
    {
        return "languages.{$this->locale}";
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
