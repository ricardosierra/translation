<?php

use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code')->unique();
            $table->primary('code');
            $table->string('lang_code')->nullable();
            $table->string('name')->nullable();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('languages', function ($table) {
            $table->increments('id');
            $table->string('locale', 10)->unique();
            $table->string('name', 60)->unique();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('translations', function ($table) {
            $table->increments('id');
            $table->string('locale', 10);
            $table->string('namespace')->default('*');
            $table->string('group');
            $table->string('item');
            $table->text('text');
            $table->boolean('unstable')->default(false);
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->foreign('locale')->references('locale')->on('languages');
            $table->unique(['locale', 'namespace', 'group', 'item']);
        });

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('translations');

        Schema::drop('translations');
        Schema::drop('languages');
        Schema::dropIfExists('locales');
    }
}
