<?php

namespace App\Services;


use Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class WordFrequencyService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Validate the incoming request data.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateRequest(array $data): array
    {
        $validator = Validator::make($data, [
            'text' => 'nullable|required_without:text-file|string', // Text is optional but required if text-file is not present
            'text-file' => 'nullable|required_without:text|file|mimes:txt', // Text-file is optional but required if text is not present
            'top' => 'required|integer|min:1',
            'exclude' => 'nullable|array',
            'exclude.*' => 'string',
        ], [
            'text.required_without' => 'The text field is required when the text-file is not present.',
            'text-file.required_without' => 'The text-file field is required when the text field is not present.',
            'text-file.file' => 'The text-file must be a file.',
            'text-file.mimes' => 'The text-file must be a text file with .txt extension.',
            'top.required' => 'The top field is required.',
            'top.integer' => 'The top field must be an integer.',
            'top.min' => 'The top field must be a positive integer.',
            'exclude.array' => 'The exclude field must be an array.',
            'exclude.*.string' => 'Each item in the exclude array must be a string.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }


    /**
     * Analyze the word frequency in the given text-file or string.
     *
     * @param string $text
     * @param \Illuminate\Http\File|null $text_file
     * @param int $top
     * @param array $exclude
     * @return array
     */
    public function analyze($text, ?UploadedFile $text_file, int $top, array $exclude = []): array
    {
        $cacheKey = $this->generateCacheKey($text, $text_file, $exclude);

        // Check if the result is cached
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult) {
            var_dump("cache result");
            return collect($cachedResult)
                ->map(fn($count, $word) => ['word' => $word, 'count' => $count])
                ->values()
                ->toArray();  // Return cached result if available
        }

        $exclude = array_map('strtolower', $exclude);

        // If a file is provided, process the file in chunks
        if ($text_file && $text_file->isValid()) {
            $frequencies = $this->processFileInChunks($text_file, $exclude);
        } else {
            // Process text input if no file
            $frequencies = $this->processText($text, $exclude);
        }

        // descending order
        arsort($frequencies);

        // Limit to top N most frequent words
        $final_result = array_slice($frequencies, 0, $top, true);

        Cache::put($cacheKey, $final_result, now()->addMinutes(10));

        //result
        return collect($final_result)->map(fn($count, $word) => ['word' => $word, 'count' => $count])->values()->toArray();
    }


    /**
     * Process the text and analyze the word frequency.
     *
     * @param string $text
     * @param array $exclude
     * @return string
     */
    private function processText(string $text, array $exclude): array
    {
        //split words (case insensitive)
        preg_match_all('/\b\w+\b/', strtolower($text), $matches);

        // Count word frequencies
        $frequencies = array_count_values($matches[0]);

        // Exclude stopwords
        foreach ($exclude as $word) {
            unset($frequencies[$word]);
        }

        return $frequencies;
    }
    /**
     * Process the file in chunks and analyze the word frequency.
     *
     * @param \Illuminate\Http\File $file
     * @param array $exclude
     * @return string
     */
    private function processFileInChunks($file, $exclude)
    {   
        $handle = fopen($file->getRealPath(), 'r');
        $text = '';
        $exclude = array_map('strtolower', $exclude);
    
        // Process file in chunks 256KB per chunk
        while (!feof($handle)) {
            $chunk = fread($handle, 256 * 1024);  // Read 256KB chunk
            $text .= strtolower($chunk);  // Convert to lowercase and append to text
        }
    
        fclose($handle);
    
        // Normalize stopwords and process word frequency
        $words = str_word_count($text, 1);
        $frequencies = array_count_values($words);
    
        // Exclude stopwords
        foreach ($exclude as $word) {
            unset($frequencies[$word]);
        }
    
        // Sort words by frequency in descending order
        arsort($frequencies);
    
        return $frequencies;
    }

    /**
     * Generate a unique cache key based on the input text/file and exclude list.
     *
     * @param string|null $text
     * @param \Illuminate\Http\UploadedFile|null $text_file
     * @param array $exclude
     * @return string
     */
    private function generateCacheKey(?string $text, ?UploadedFile $text_file, array $exclude): string
    {
        // You can hash the input text or the file content to create a unique cache key
        if ($text) {
            return 'word-frequency-' . md5($text . implode(',', $exclude));
        }

        if ($text_file) {
            $fileContent = file_get_contents($text_file->getRealPath());
            return 'word-frequency-file-' . md5($fileContent . implode(',', $exclude));
        }

        return 'word-frequency-default';
    }
}
