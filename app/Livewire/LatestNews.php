<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use Livewire\Component;
use App\Services\HttpService;

class LatestNews extends Component
{
    public $selectedApi;
    public $news;
    protected $httpService;

    public function __construct()
    {
        $this->httpService = app(HttpService::class);
    }

    /*public function fetchNews()
    {
        if (filter_var($this->selectedApi, FILTER_VALIDATE_URL) === FALSE) {
            $this->news = 'Invalid URL';
            return;
        }

        $this->news = json_decode($this->httpService->getRequest($this->selectedApi), true);

    }*/
    public function fetchNews()
{
    // Lista URL consentiti (whitelist)
    $allowedApis = [
        'it' => 'https://newsapi.org/v2/top-headlines?country=it&apiKey=' . env('NEWS_API_KEY'),
        'en' => 'https://newsapi.org/v2/top-headlines?country=us&apiKey=' . env('NEWS_API_KEY'),
    ];

    // Verifica che $selectedApi corrisponda a una chiave valida
    if (!isset($allowedApis[$this->selectedApi])) {
        $this->news = ['error' => 'API non autorizzata'];
        return;
    }

    // Usa solo URL consentiti
    $url = $allowedApis[$this->selectedApi];

    $this->news = json_decode($this->httpService->getRequest($url), true);
}

    public function render()
    {
        return view('livewire.latest-news');
    }
}
