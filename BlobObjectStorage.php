<?php

/**
 * @file
 * Blob Object Storage Class
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage;

use Dbp\Relay\BlobLibrary\Api\BlobFile;
use Kanboard\Core\ObjectStorage\ObjectStorageException;
use Kanboard\Core\ObjectStorage\ObjectStorageInterface;
use Kanboard\Plugin\BlobStorage\Helper\BlobHelper;
use Dbp\Relay\BlobLibrary\Api\BlobApi;
use Dbp\Relay\BlobLibrary\Api\BlobApiError;

/**
 * Blob Object Storage
 *
 * @category File-storage
 * @package  BlobObjectStorage
 */
class BlobObjectStorage implements ObjectStorageInterface
{
    private BlobApi $blobApi;

    /**
     * @throws BlobApiError
     */
    public function __construct(
        string $bucketKey,
        string $bucketIdentifier,
        string $blobBaseUrl,
        string $oidcProviderUrl,
        string $oidcClientId,
        string $oidcClientSecret
    ) {
        $this->blobApi = BlobApi::createHttpModeApi(
            $bucketIdentifier, $bucketKey, $blobBaseUrl,
            true, $oidcProviderUrl, $oidcClientId, $oidcClientSecret);
    }

    /**
     * Fetch file contents from Object Storage.
     * Not in use...
     *
     * @access public
     *
     * @param string $key file key
     *
     * @return string the file contents
     *
     * @throws ObjectStorageException
     */
    public function get($key): string
    {
        return $this->getFileContents($key);
    }

    /**
     * Output file content directly from object storage.
     * Used by Download method and image gallery.
     *
     * @access public
     *
     * @param string $key file key
     *
     * @throws ObjectStorageException
     */
    public function output($key): void
    {
        echo $this->getFileContents($key);
    }

    /**
     * Upload the file to object storage
     *
     * @access public
     *
     * @param string $filename  array contain local file data
     * @param string $key           file key and orig filename
     *
     * @throws ObjectStorageException
     */
    public function moveFile($filename, $key): bool
    {
        try {
            if (BlobHelper::checkIfFileIsAllowed($filename)) {
                $blobFile = new BlobFile();
                $blobFile->setFile(new \SplFileInfo($filename));
                $blobFile->setFilename(basename($filename));
                $blobFile->setPrefix($key);
                $this->blobApi->addFile($blobFile);

                return true;
            } else {
                throw new ObjectStorageException(e('File type is not allowed. Only images, documents and zip files are allowed.'));
            }
        } catch (BlobApiError $blobApiError) {
            throw new ObjectStorageException(sprintf('Unable to upload file \'%s\': %s',
                $key, BlobHelper::getBlobErrorMessage($blobApiError)));
        }
    }

    /**
     * Upload image to object storage.
     *
     * @access public
     *
     * @param string $key   blob key and orig filename
     * @param string $blob  file content blob
     *
     * @throws ObjectStorageException
     */
    public function put($key, &$blob): void
    {
        try {
            if (BlobHelper::checkIfFileIsAllowed($blob)) {
                $blobFile = new BlobFile();
                $blobFile->setFile($blob);
                $blobFile->setFilename(basename($key));
                $blobFile->setPrefix($key);
                $this->blobApi->addFile($blobFile);
            } else {
                throw new ObjectStorageException(e('File type is not allowed. Only images, documents and zip files are allowed.'));
            }
        } catch (BlobApiError $blobApiError) {
            throw new ObjectStorageException(sprintf('Failed to upload file: %s',
                BlobHelper::getBlobErrorMessage($blobApiError)));
        }
    }

    /**
     * Move the uploaded file to object storage
     *
     * @access public
     *
     * @param string $filename array contain local file data
     * @param string $key      blob key
     *
     * @throws ObjectStorageException
     */
    public function moveUploadedFile($filename, $key): bool
    {
        return $this->moveFile($filename, $key);
    }

    /**
     * Delete file from object storage.
     *
     * @access public
     *
     * @param  string $key blob key
     *
     * @throws ObjectStorageException
     *
     */
    public function remove($key): bool
    {
        try {
            $this->blobApi->removeFiles([
                BlobApi::PREFIX_OPTION => $key,
            ]);

            return true;
        } catch (BlobApiError $e) {
            throw new ObjectStorageException(sprintf('File \'%s\' could not be deleted from Blob: %s',
                $key, BlobHelper::getBlobErrorMessage($e)));
        }
    }

    /**
     * @throws ObjectStorageException
     */
    private function getFileContents(string $key): string|false
    {
        try {
            $blobFile = null;
            foreach ($this->blobApi->getFiles(options: [
                BlobApi::PREFIX_OPTION => $key,
                BlobApi::INCLUDE_FILE_CONTENTS_OPTION => true]) as $blobFile) {
                break;
            }
            if ($blobFile !== null) {
                return base64_decode(explode(',', $blobFile->getContentUrl())[1], true);
            } else {
                throw new ObjectStorageException(sprintf('Failed to download file \'%s\': file not found', $key));
            }
        } catch (BlobApiError $blobApiError) {
            throw new ObjectStorageException(sprintf('Failed to download file \'%s\': %s',
                $key, BlobHelper::getBlobErrorMessage($blobApiError)));
        }
    }
}
