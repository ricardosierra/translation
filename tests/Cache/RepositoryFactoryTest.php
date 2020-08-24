<?php

namespace Translation\Test\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Translation\Cache\RepositoryFactory;
use Translation\Cache\SimpleRepository;
use Translation\Cache\TaggedRepository;
use Translation\Test\TestCase;

class RepositoryFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        // During the parent's setup, both a 'es' 'Spanish' and 'en' 'English' languages are inserted into the database.
        parent::setUp();
    }

    /**
     * @test
     */
    public function test_returns_simple_cache_if_non_taggable_store()
    {
        $store = new FileStore(\App::make('files'), __DIR__ . '/temp');
        $repo  = RepositoryFactory::make($store, 'translation');
        $this->assertEquals(SimpleRepository::class, get_class($repo));
    }

    /**
     * @test
     */
    public function test_returns_simple_cache_if_taggable_store()
    {
        $store = new ArrayStore;
        $repo  = RepositoryFactory::make($store, 'translation');
        $this->assertEquals(TaggedRepository::class, get_class($repo));
    }
}
