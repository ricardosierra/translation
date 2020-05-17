<?php

namespace RicardoSierra\Translation\Clients;

use RicardoSierra\Translation\Contracts\Client;
use RicardoSierra\Translation\GoogleTranslate as TranslateClient;

class GoogleTranslate implements Client
{
    /**
     * @var TranslateClient 
     */
    protected $client;

    /**
     * @param TranslateClient $client
     */
    public function __construct(TranslateClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function setSource($source = null)
    {
        return $this->client->setSource($source);
    }

    /**
     * {@inheritdoc}
     */
    public function setTarget($target)
    {
        return $this->client->setTarget($target);
    }

    /**
     * {@inheritdoc}
     */
    public function translate($text)
    {
        return $this->client->translate($text);
    }
}
