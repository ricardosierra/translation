<?php

namespace Translation\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Translation\Facades\Translation;
use Siravel\Models\Negocios\Page;
use Translation\Traits\HasTranslations;
use Translation\Translator;
use Translation\Translator\Collection;

class MultilingualTest extends TestCase
{
    protected function setUp(): void: void
    {
        parent::setUp();

        // Add another language
        \Illuminate\Support\Facades\Config::get()->set('facilitador.multilingual.locales', ['en', 'da']);

        // Turn on multilingual
        \Illuminate\Support\Facades\Config::get()->set('facilitador.multilingual.enabled', true);
    }

    public function testCheckingModelIsTranslatable()
    {
        $this->assertTrue(Translation::translatable(TranslatableModel::class));
        $this->assertTrue(Translation::translatable(ActuallyTranslatableModel::class));
    }

    public function testCheckingModelIsNotTranslatable()
    {
        $this->assertFalse(Translation::translatable(NotTranslatableModel::class));
        $this->assertFalse(Translation::translatable(StillNotTranslatableModel::class));
    }

    public function testGettingModelTranslatableAttributes()
    {
        $this->assertEquals(['title'], (new TranslatableModel())->getTranslatableAttributes());
        $this->assertEquals([], (new ActuallyTranslatableModel())->getTranslatableAttributes());
    }

    public function testGettingTranslatorCollection()
    {
        $collection = TranslatableModel::all()->translate('da');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Translator::class, $collection[0]);
    }

    public function testGettingTranslatorModelOfNonExistingTranslation()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Lorem Ipsum Post', $model->title);
        $this->assertFalse($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));
    }

    public function testGettingTranslatorModelOfExistingTranslation()
    {
        DB::table('translations')->insert(
            [
            'table_name'  => 'posts',
            'column_name' => 'title',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Foo Bar Post',
            ]
        );

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Foo Bar Post', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));
    }

    public function testSavingNonExistingTranslatorModel()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Lorem Ipsum Post', $model->title);
        $this->assertFalse($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model->title = 'Danish Title';

        $this->assertEquals('Danish Title', $model->title);
        $this->assertFalse($model->getRawAttributes()['title']['exists']);
        $this->assertTrue($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model->save();

        $this->assertEquals('Danish Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));
    }

    public function testSavingExistingTranslatorModel()
    {
        DB::table('translations')->insert(
            [
            'table_name'  => 'posts',
            'column_name' => 'title',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Foo Bar Post',
            ]
        );

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Foo Bar Post', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model->title = 'Danish Title';

        $this->assertEquals('Danish Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertTrue($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model->save();

        $this->assertEquals('Danish Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
        $this->assertEquals('Lorem Ipsum Post', $model->getOriginalAttribute('title'));
    }

    public function testGettingTranslationMetaDataFromTranslator()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertFalse($model->translationAttributeExists('title'));
        $this->assertFalse($model->translationAttributeModified('title'));
    }

    public function testCreatingNewTranslation()
    {
        $model = TranslatableModel::first()->translate('da');

        $model->createTranslation('title', 'Danish Title Here');

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title Here', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
    }

    public function testUpdatingTranslation()
    {
        DB::table('translations')->insert(
            [
            'table_name'  => 'posts',
            'column_name' => 'title',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Title',
            ]
        );

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);

        $model->title = 'New Title';

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertTrue($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);

        $model->save();

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->title);
        $this->assertTrue($model->getRawAttributes()['title']['exists']);
        $this->assertFalse($model->getRawAttributes()['title']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['title']['locale']);
    }

    public function testSearchingTranslations()
    {
        //Default locale
        $this->assertEquals(Page::whereTranslation('slug', 'hello-world')->count(), 1);

        //Default locale, but default excluded
        $this->assertEquals(Page::whereTranslation('slug', '=', 'hello-world', [], false)->count(), 0);

        //Translation, all locales
        $this->assertEquals(Page::whereTranslation('slug', 'ola-mundo')->count(), 1);

        //Translation, wrong locale-array
        $this->assertEquals(Page::whereTranslation('slug', '=', 'ola-mundo', ['de'])->count(), 0);

        //Translation, correct locale-array
        $this->assertEquals(Page::whereTranslation('slug', '=', 'ola-mundo', ['de', 'pt'])->count(), 1);

        //Translation, wrong locale
        $this->assertEquals(Page::whereTranslation('slug', '=', 'ola-mundo', 'de')->count(), 0);

        //Translation, correct locale
        $this->assertEquals(Page::whereTranslation('slug', '=', 'ola-mundo', 'pt')->count(), 1);
    }
}

class TranslatableModel extends Model
{
    protected $table = 'posts';

    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'body',
    ];

    protected $translatable = ['title'];
}

class NotTranslatableModel extends Model
{
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'body',
    ];
}

class StillNotTranslatableModel extends Model
{
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'body',
    ];

    protected $translatable = ['title'];
}

class ActuallyTranslatableModel extends Model
{
    protected $table = 'posts';

    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'body',
    ];
}
