<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('translations')) {
            Schema::create(
                'translations', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('locale', 10);
                    $table->string('namespace')->default('*');
                    $table->string('group');
                    $table->string('item');
                    $table->text('text');
                    $table->boolean('unstable')->default(false);
                    $table->boolean('locked')->default(false);

                    $table->unique(['locale', 'namespace', 'group', 'item']);

                    $table->timestamps();
                    $table->softDeletes();

                    // @todo Tava no CMS
                    // $table->increments('id');
                    // $table->integer('entity_id');
                    // $table->string('entity_type');
                    // $table->text('entity_data')->nullable();
                    // $table->nullableTimestamps();
                    // $table->string('language')->nullable();
                }
            );
        }

        try {
            if (!Schema::hasTable('model_translations')) {
                Schema::create(
                    'model_translations', function (Blueprint $table) {
                        $table->increments('id');

                        $table->string('entity_id');
                        $table->string('entity_type');
                        $table->string('entity_data')->nullable();

                        
                        $table->string('language_code');
                        $table->string('country_code')->nullable();
                    

                        $table->foreign('language_code')->references('code')->on('languages');
                        $table->foreign('country_code')->references('code')->on('countries');

                        $table->unique(['entity_id', 'entity_type', 'language_code', 'country_code'], 'translation_unique');
                        // $table->primary(['entity_id', 'entity_type', 'language_code', 'country_code']); @todo

                        $table->timestamps();
                    }
                );
            }
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_translations');
        Schema::dropIfExists('translations');
    }
}
