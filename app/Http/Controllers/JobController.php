<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class JobController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'urls' => 'required|array',
            'selectors' => 'required|array',
        ]);

        $jobId = Redis::incr('job_counter');
        $jobKey = "job:{$jobId}";

        // Encode URLs and selectors without escaping characters
        $urls = json_encode($request->input('urls'), JSON_UNESCAPED_SLASHES);
        $selectors = json_encode($request->input('selectors'), JSON_UNESCAPED_SLASHES);

        Redis::hset($jobKey, 'id', $jobId);
        Redis::hset($jobKey, 'urls', $urls);
        Redis::hset($jobKey, 'selectors', $selectors);
        Redis::hset($jobKey, 'status', 'queued');

        dispatch(new \App\Jobs\JobScrapping($jobKey));

        return response()->json(['message' => 'Job created successfully', 'job_id' => $jobId], 201);
    }

    public function retrieve($id)
    {
        $jobKey = "job:{$id}";

        if (!Redis::exists($jobKey)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $jobData = Redis::hgetall($jobKey);

        return response()->json(['job' => $jobData, 'scraped_data' => $this->getAllScrapedData($id)]);
    }

    public function delete($id)
    {
        $jobKey = "job:{$id}";

        if (!Redis::exists($jobKey)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        Redis::del($jobKey);

        // Delete scraped data keys
        for ($index = 0; Redis::exists("job:{$id}:data:{$index}"); $index++) {
            Redis::del("job:{$id}:data:{$index}");
        }

        return response()->json(['message' => 'Job deleted successfully']);
    }

    protected function getAllScrapedData($id)
    {
        $allScrapedData = [];

        for ($index = 0; Redis::exists("job:{$id}:data:{$index}"); $index++) {
            $redisKey = "job:{$id}:data:{$index}";
            $allScrapedData[$index] = Redis::hgetall($redisKey);
        }

        return $allScrapedData;
    }
}
