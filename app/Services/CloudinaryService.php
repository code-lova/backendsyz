<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private $cloudinary;
    private $isConfigured;

    public function __construct()
    {
        try {
            $cloudName = env('CLOUDINARY_CLOUD_NAME');
            $apiKey = env('CLOUDINARY_API_KEY');
            $apiSecret = env('CLOUDINARY_API_SECRET');

            // Check if all required environment variables are set
            if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
                Log::warning('Cloudinary configuration incomplete', [
                    'cloud_name' => !empty($cloudName),
                    'api_key' => !empty($apiKey),
                    'api_secret' => !empty($apiSecret)
                ]);
                $this->isConfigured = false;
                return;
            }

            $this->cloudinary = new \Cloudinary\Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
            ]);

            $this->isConfigured = true;

        } catch (\Exception $e) {
            Log::error('Failed to initialize Cloudinary service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->isConfigured = false;
        }
    }

    /**
     * Check if Cloudinary is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Delete an image from Cloudinary
     *
     * @param string $publicId
     * @return array|null
     */
    public function deleteImage(string $publicId): ?array
    {
        if (!$this->isConfigured) {
            Log::warning('Attempted to delete image but Cloudinary is not configured', [
                'public_id' => $publicId
            ]);
            return null;
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            Log::info('Cloudinary image deleted successfully', [
                'public_id' => $publicId,
                'result' => $result
            ]);

            return $result->getArrayCopy();
        } catch (\Exception $e) {
            Log::warning('Failed to delete Cloudinary image', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Upload an image to Cloudinary
     *
     * @param string $filePath
     * @param array $options
     * @return array|null
     */
    public function uploadImage(string $filePath, array $options = []): ?array
    {
        if (!$this->isConfigured) {
            Log::warning('Attempted to upload image but Cloudinary is not configured', [
                'file_path' => $filePath
            ]);
            return null;
        }

        try {
            $result = $this->cloudinary->uploadApi()->upload($filePath, $options);

            Log::info('Cloudinary image uploaded successfully', [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url']
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to upload image to Cloudinary', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Get optimized image URL with transformations
     *
     * @param string $publicId
     * @param array $transformations
     * @return string
     */
    public function getOptimizedUrl(string $publicId, array $transformations = []): string
    {
        if (!$this->isConfigured) {
            Log::warning('Attempted to get optimized URL but Cloudinary is not configured', [
                'public_id' => $publicId
            ]);
            return '';
        }

        try {
            $defaultTransformations = [
                'fetch_format' => 'auto',
                'quality' => 'auto:good',
            ];

            $transformations = array_merge($defaultTransformations, $transformations);

            return $this->cloudinary->image($publicId)
                ->addTransformation($transformations)
                ->toUrl();
        } catch (\Exception $e) {
            Log::warning('Failed to generate optimized Cloudinary URL', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);

            return '';
        }
    }

    /**
     * Delete multiple images from Cloudinary
     *
     * @param array $publicIds
     * @return array
     */
    public function deleteMultipleImages(array $publicIds): array
    {
        if (!$this->isConfigured) {
            Log::warning('Attempted to delete multiple images but Cloudinary is not configured', [
                'public_ids_count' => count($publicIds)
            ]);
            return array_fill_keys($publicIds, null);
        }

        $results = [];

        foreach ($publicIds as $publicId) {
            $results[$publicId] = $this->deleteImage($publicId);
        }

        return $results;
    }
}
