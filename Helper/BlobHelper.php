<?php
/**
 * @file
 * Blob Helper Class
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Helper;

use Kanboard\Core\Base;
use Throwable;

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
     * @return array An array containing the filename and prefix for blob
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
     * @param Throwable $e The original Exception
     * @return string Formatted error message
     */
    public static function getBlobErrorMessage(Throwable $e): string
    {
        $blobErrorMessage = json_decode($e->getMessage());
        if ($blobErrorMessage) {
            $errorId = $blobErrorMessage->errorId;
            $errorMessage = $blobErrorMessage->message;
            return $errorId . ': ' . $errorMessage;
        } else {
            return 'Error message not found';
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

        return in_array($mimeType, self::VALID_TYPES);
    }

    function isResource($resource) {
        if (is_resource($resource)) {
            $resourceType = get_resource_type($resource);
            if ($resourceType === 'stream') {
                echo "This is a stream resource.";
            } else {
                echo "This is a resource but not a stream.";
            }
        } elseif (is_string($resource)) {
            echo "This is a string.";
        } else {
            echo "This is not a valid resource or string.";
        }
    }
}
