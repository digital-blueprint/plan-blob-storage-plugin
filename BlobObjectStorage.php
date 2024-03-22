<?php

/**
 * @file
 * Blob Object Storage Class
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage;

use Kanboard\Core\ObjectStorage\ObjectStorageException;
use Kanboard\Core\ObjectStorage\ObjectStorageInterface;
use Kanboard\Plugin\BlobStorage\Helper\BlobHelper;

use Dbp\Relay\BlobLibrary\Api\BlobApi;
use Dbp\Relay\BlobLibrary\Api\BlobApiError;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Blob Object Storage
 *
 * @category File-storage
 * @package  BlobObjectStorage
 */
class BlobObjectStorage implements ObjectStorageInterface
{
    /**
     * @var BlobApi
     */
    private $blobApi;

    /**
     * @var string blobBaseUrl
     */
    private $blobBaseUrl;

    /**
     * @var string blobBucketId
     */
    private $blobBucketId;

    /**
     * @var string blobKey
     */
    private $blobKey;

    /**
     * @var string oauthIDPUrl
     */
    private $oauthIDPUrl;

    /**
     * @var string clientID
     */
    private $clientID;

    /**
     * @var string clientSecret
     */
    private $clientSecret;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct(
        string $blobKey,
        string $blobBucketId,
        string $blobBaseUrl,
        string $oauthIDPUrl,
        string $clientID,
        string $clientSecret
    ) {
        $this->blobBaseUrl = $blobBaseUrl;
        $this->blobBucketId = $blobBucketId;
        $this->blobKey = $blobKey;

        $this->blobApi = new BlobApi($this->blobBaseUrl, $this->blobBucketId, $this->blobKey);

        $this->oauthIDPUrl = $oauthIDPUrl;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;

        try {
            $this->blobApi->setOAuth2Token($oauthIDPUrl, $clientID, $clientSecret);
        } catch (\JsonException $e) {
            echo $e->getMessage() . "\n";
            throw new BlobApiError('Something went wrong while decoding the json!', 'blob-library:get-token-json-error', ['message' => $e->getMessage()]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            echo $e->getMessage() . "\n";
            throw new BlobApiError('Something went wrong in the request!', 'blob-library:get-token-request-error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Fetch file contents from Object Storage.
     * Not in use...
     *
     * @access public
     *
     * @param string $prefix blob key
     *
     * @return string blob contents
     *
     * @throws ObjectStorageException
     */
    public function get($prefix): string
    {
        try {
            $fileData = $this->blobApi->getFileDataByPrefix($prefix);
            if (is_array($fileData) && isset($fileData["hydra:member"][0]["contentUrl"])) {
                return base64_decode(explode(',', $fileData["hydra:member"][0]["contentUrl"])[1], true);
            }
        } catch (BlobApiError $e) {
            $errorMessage = BlobHelper::getBlobErrorMessage($e);
            throw new ObjectStorageException(e('File could not be downloaded from Blob! %s', $errorMessage));
        }
    }

    /**
     * Output file content directly from object storage.
     * Used by Download method and image gallery.
     *
     * @access public
     *
     * @param string $prefix blob key
     *
     * @return void
     *
     * @throws ObjectStorageException
     */
    public function output($prefix): void
    {
        try {
            $fileData = $this->blobApi->getFileDataByPrefix($prefix);
            if (is_array($fileData) && isset($fileData["hydra:member"][0]["contentUrl"])) {
                echo base64_decode(explode(',', $fileData["hydra:member"][0]["contentUrl"])[1], true);
            }
        } catch (BlobApiError $e) {
            $errorMessage = BlobHelper::getBlobErrorMessage($e);
            throw new ObjectStorageException(e('Unable to output file. %s', $errorMessage));
        }
    }

    /**
     * Upload file to object storage
     *
     * @access public
     *
     * @param string $file_tmp_src  array contain local file data
     * @param string $key           blob key and orig filename
     *
     * @return boolean
     *
     * @throws ObjectStorageException
     */
    public function moveFile($file_tmp_src, $key): bool
    {
        try {
            if (BlobHelper::checkIfFileIsAllowed($file_tmp_src)) {
                list($filename, $prefix) = BlobHelper::getFilenameAndPrefixFromKey($key);
                if ($filename && $prefix) {
                    $this->blobApi->uploadFile($prefix, $filename, file_get_contents($file_tmp_src));
                    return true;
                } else {
                    throw new ObjectStorageException(e('Unable to upload file. Wrong key supplied.'));
                }
            } else {
                throw new ObjectStorageException(e('File type is not allowed. Only images, documents and zip files are allowed.'));
            }
        } catch (BlobApiError $e) {
            $errorMessage = BlobHelper::getBlobErrorMessage($e);
            throw new ObjectStorageException(e('Unable to upload file. %s', $errorMessage));
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
                list($filename, $prefix) = BlobHelper::getFilenameAndPrefixFromKey($key);
                if ($filename && $prefix) {
                    $this->blobApi->uploadFile($prefix, $filename, $blob);
                } else {
                    throw new ObjectStorageException(e('Unable to upload file. Wrong key supplied.'));
                }
            } else {
                throw new ObjectStorageException(e('File type is not allowed. Only images, documents and zip files are allowed.'));
            }
        } catch (BlobApiError $e) {
            $errorMessage = BlobHelper::getBlobErrorMessage($e);
            throw new ObjectStorageException(e('Unable to upload file. %s', $errorMessage));
        }
    }

    /**
     * Move uploaded file to object storage
     *
     * @access public
     *
     * @param string  $file_tmp_src array contain local file data
     * @param string $key  blob key
     *
     * @return boolean
     */
    public function moveUploadedFile($file_tmp_src, $key)
    {
        return $this->moveFile($file_tmp_src, $key);
    }

    /**
     * Delete file from object storage.
     *
     * @access public
     *
     * @param  string $prefix blob key
     *
     * @throws ObjectStorageException
     *
     * @return boolean
     */
    public function remove($prefix): bool
    {
        try {
            $isDeleted = $this->blobApi->deleteFilesByPrefix($prefix);
            return true;
        } catch (BlobApiError $e) {
            $errorMessage = BlobHelper::getBlobErrorMessage($e);
            throw new ObjectStorageException(e('Files could not be deleted from Blob! %s', $errorMessage));
        }
    }
}
