<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class JobScrapping implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobKey;

    public function __construct($jobKey)
    {
        $this->jobKey = $jobKey;
    }

    public function handle()
    {
        $jobData = Redis::hgetall($this->jobKey);
        $urls = json_decode($jobData['urls'], true); 
        $selectors = json_decode($jobData['selectors'], true); 

        $client = new Client();
        $scrapedData = [];
        
        foreach ($urls as $index => $url) {
            try {
                $crawler = new Crawler($client->request('GET', $url)->getBody());
            } catch (\Exception $e) {
                continue; 
            }

            $data = [];

            foreach ($selectors as $selector) {
                //$data[$selector] = $crawler->filter($selector)->text();
                $data[$selector] = $crawler->filter($selector)->each(function (Crawler $node) {
                    return $node->text();
                });
            }

            // Save scraped data to Redis
            $redisKey = "{$this->jobKey}:data:{$index}";

            foreach ($data as $field => $values) {
                // Save multiple values as an array
                Redis::hset($redisKey, $field, json_encode($values));
            }

            $scrapedData[] = $data;
        }

        // Update job status
        Redis::hset($this->jobKey, 'status', 'completed');
    }
}