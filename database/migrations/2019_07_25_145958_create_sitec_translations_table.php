<?php


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSitecTranslationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    //     if (!Schema::hasColumn('flights', 'departure_time')) {
    //   $table->timestamp('departure_time');
    //     } 
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->string('code')->unique();
                $table->primary('code');
                $table->string('name');
    
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->string('code')->unique();
                $table->primary('code');

                $table->integer('position')->nullable();
                $table->string('name', 50);
                $table->boolean('is_default')->default(false);

                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('locales')) {
            Schema::create('locales', function (Blueprint $table) {
                $table->string('language')->unique();
                $table->string('country')->nullable();
                
                $table->primary(['language','country']);

                $table->foreign('language')->references('code')->on('languages');
                $table->foreign('country')->references('code')->on('countries');

                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('model_translations')) {
            Schema::create('model_translations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('locale', 10);
                $table->string('namespace')->default('*');
                $table->string('group');
                $table->string('item');
                $table->text('text');
                $table->boolean('unstable')->default(false);
                $table->boolean('locked')->default(false);

                $table->foreign('locale')->references('code')->on('languages');
                $table->unique(['locale', 'namespace', 'group', 'item']);

                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Schema::create('translations', function (Blueprint $table) {
        //     $table->increments('id');
        //     $table->timestamps();
        //     $table->integer('locale_id')->unsigned();
        //     $table->integer('translation_id')->unsigned()->nullable();
        //     $table->text('translation');

        //     $table->foreign('locale_id')->references('id')->on('locales')
        //         ->onUpdate('restrict')
        //         ->onDelete('cascade');

        //     $table->foreign('translation_id')->references('id')->on('translations')
        //         ->onUpdate('restrict')
        //         ->onDelete('cascade');
        // });


        
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
        });

        // Note: Laravel does not support spatial types.
        // See: https://dev.mysql.com/doc/refman/5.7/en/spatial-type-overview.html
        DB::statement("ALTER TABLE `locations` ADD `coordinates` POINT;");

        /**
         * Carrega Paises
         */
        $langs = \Illuminate\Support\Facades\Config::get('translation.countries');
        if (!empty($langs)) {
            $class = \Illuminate\Support\Facades\Config::get('translation.models.country');
            foreach($langs as $code=>$name) {
                $language = new $class;
                $language->name = $name;
                $language->code = $code;
                $language->save();
            }
        }

        /**
         * Carrega Linguagens
         */
        $langs = \Illuminate\Support\Facades\Config::get('translation.locales');
        if (!empty($langs)) {
            $class = \Illuminate\Support\Facades\Config::get('translation.models.language');
            foreach($langs as $code=>$name) {
                $language = new $class;
                $language->name = $name;
                $language->code = $code;
                $language->save();
            }
        }

        /**
         * Localizações principais Principais
         */
        $class = \Illuminate\Support\Facades\Config::get('translation.models.locale');
        $locale = new $class;
        $locale->country = 'BR';
        $locale->language = 'pt';
        $locale->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('locales');
    }
}
