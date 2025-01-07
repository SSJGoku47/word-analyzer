<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WordFrequencyService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class WordFrequencyController extends Controller
{
    protected $wordFrequencyService;

    /**
     * Create a new controller instance.
     *
     * @param WordFrequencyService $wordFrequencyService
     */
    public function __construct(WordFrequencyService $wordFrequencyService)
    {
        $this->wordFrequencyService = $wordFrequencyService;
    }

    /**
     * Handle the word frequency analysis.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyze(Request $request)
    {   
        try {
            // Validate incoming data
            $validatedData = $this->wordFrequencyService->validateRequest($request->all());

            $text = $validatedData['text'] ?? null;
            $text_file = $validatedData['text-file'] ?? null;
            $top = $validatedData['top'];
            $exclude = $validatedData['exclude'] ?? [];

            // Analyze the word frequency
            $result = $this->wordFrequencyService->analyze($text, $text_file, $top, $exclude);

            // Return the result
            return response()->json(['data' => $result], 200);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

}
