<?php
/**
 * @file
 * Blob Helper Class
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Helper;

use Exception;
use Kanboard\Core\Base;
use Dbp\Relay\BlobLibrary\Api\BlobApiError;

/**
 * Blob Helper functions
 *
 * @package helper
 * @author  David Zsuffa
 */
class BlobHelper extends Base
{
    /**
     * An array of valid MIME types for file uploads.
     * Defaults if no MIME type is specified in config form.
     */
    const VALID_TYPES = [
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/bmp',
        'image/gif',
        'image/tiff',
        'image/webp',
        /* svg */
        'image/svg+xml',
        /* pdf */
        'application/pdf',
        /* word */
        'application/rtf',
        'application/doc',
        'application/ms-doc',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        /* excel */
        'application/excel',
        'application/vnd.ms-excel',
        'application/x-excel',
        'application/x-msexcel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        /* powerpoint */
        'application/mspowerpoint',
        'application/powerpoint',
        'application/vnd.ms-powerpoint',
        'application/x-mspowerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        /* zip */
        'application/zip',
        'application/x-7z-compressed'
    ];

    /**
     * Create filename and prefix from Kanboard file hash ID.
     *
     * @param string $key Kanboard file hash ID
     * @return array<mixed> An array containing the filename and prefix for blob
     */
    public static function getFilenameAndPrefixFromKey(string $key): array
    {
        if (!is_string($key)) {
            return [false, false];
        }
        $path_info = pathinfo($key);
        $filename = $path_info['basename'];
        $prefix = ltrim($path_info['dirname'], '/');

        return [$filename, $prefix];
    }

    /**
     * Generates a special key for a blob containing the Kanboard file hash ID and the original filename.
     *
     * @param string $originalFilename The original filename
     * @param string $destinationFilename The Kanboard filename hash ID
     * @return string The special blob key "/tasks/1/e4be10d43b5845c993a3059eb0ba74e4009e39da/filename.ext"
     */
    public static function generateBlobKeyFilename(string $originalFilename, string $destinationFilename): string
    {
        $originalFilename = strtr($originalFilename, ['/' => '-', ' ' => '_']);

        return $destinationFilename . DIRECTORY_SEPARATOR . $originalFilename;
    }

    /**
     * Format the error message thrown by Blob Library.
     *
     * @param BlobApiError $blobApiError Blob Api Exception
     * @return string Formatted error message
     */
    public static function getBlobErrorMessage(BlobApiError $blobApiError): string
    {
        return $blobApiError->getErrorId().': '.$blobApiError->getMessage().
            ($blobApiError->getBlobErrorId() ? '('.$blobApiError->getBlobErrorId().')' : '');
    }

    /**
     * Check if file size is within configured limits
     *
     * @param int $uploadedFileSize Uploaded file size in bytes.
     * @throws Exception
     * @return void
     */
    public function checkAllowedUploadSize($uploadedFileSize): void
    {
        $maxAllowedFileSizeMb = intval($this->configModel->get('blob_allowed_max_file_upload_size'));
        $maxAllowedFileSize = $maxAllowedFileSizeMb * 1024 * 1024;
        if ($uploadedFileSize > $maxAllowedFileSize) {
            $uploadedFileSizeMb = number_format($uploadedFileSize / (1024 * 1024), 2);
            throw new Exception(e('File too large to be uploaded (%d MB). Maximum configured file size is: %d MB', $uploadedFileSizeMb, $maxAllowedFileSizeMb));
        }
    }


    /**
     * Checks if a file's MIME type is allowed to be uploaded.
     *
     * @param string $file The filepath
     * @return bool True if the file is allowed, false otherwise
     */
    public static function checkIfFileIsAllowed(string $file): bool
    {
        if (!is_string($file)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return false;
        }

        try {
            $mimeType = false;

            if (is_readable($file)) { // php7 exception on image "stream" && php 8 return false
                // Regular files: pdf, xlxs, docx.
                $mimeType = finfo_file($finfo, $file);
            } else {
                // img "stream" [php8]
                $mimeType = finfo_buffer($finfo, $file);
            }
        } catch (\TypeError $e) {
            if (is_string($file)) {
                // img "stream" [php7]
                $mimeType = finfo_buffer($finfo, $file);
            }
        }

        finfo_close($finfo);

        $instance = new self($GLOBALS['container']);
        $allowed_mime_types = $instance->getAllowedMimeTypes();

        return in_array($mimeType, $allowed_mime_types);
    }

    /**
     * Get all supported mime types from config and return them as an array.
     *
     * @return array<string>
     */
    public function getAllowedMimeTypes() :array {

        $config_allowed_mime_types = explode(',', str_replace(array("\n"), ',', $this->configModel->get('blob_allowed_mime_types')));
        // Remove empty items.
        $config_allowed_mime_types = array_filter(array_map('trim', $config_allowed_mime_types));

        empty($config_allowed_mime_types)
            ? $allowed_mime_types = self::VALID_TYPES
            : $allowed_mime_types = $config_allowed_mime_types;

        return $allowed_mime_types;
    }
}
