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
        Schema::create(
            'translations', function (Blueprint $table) {
                $table->increments('id');

                $table->string('table_name');
                $table->string('column_name');
                $table->integer('foreign_key')->unsigned();
                $table->string('locale');

                $table->text('value');

                $table->unique(['table_name', 'column_name', 'foreign_key', 'locale']);

                $table->timestamps();

                // Veio do CMS
                // $table->increments('id');
                // $table->integer('entity_id');
                // $table->string('entity_type');
                // $table->text('entity_data')->nullable();
                // $table->nullableTimestamps();
            }
        );
        // if (!Schema::hasTable('model_translations')) {
            Schema::create(
                'model_translations', function (Blueprint $table) {
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

                    // @todo Tava no CMS
                    // $table->increments('id');
                    // $table->integer('entity_id');
                    // $table->string('entity_type');
                    // $table->text('entity_data')->nullable();
                    // $table->nullableTimestamps();
                    // $table->string('language')->nullable();
                }
            );
        // }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('translations');
        Schema::drop('model_translations');
    }
}
