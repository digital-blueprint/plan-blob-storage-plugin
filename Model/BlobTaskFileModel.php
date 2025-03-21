<?php

/**
 * @file
 * Model for Blob file storage.
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Model;

use Exception;
use Kanboard\Model\TaskFileModel;
use Kanboard\Plugin\BlobStorage\Helper\BlobHelper;

/**
 * Task File Model
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class BlobTaskFileModel extends TaskFileModel
{
    /**
     * Upload multiple files
     *
     * @access public
     * @param  integer          $id     project identifier
     * @param  array<mixed>     $files  files array of files to upload
     * @return bool
     */
    public function uploadFiles($id, array $files)
    {
        if (empty($files)) {
            return false;
        }

        foreach (array_keys($files['error']) as $key) {
            $file = array(
                'name' => $files['name'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
            );


            $this->uploadFile($id, $file);
        }

        return true;
    }

    /**
     * Upload one file
     *
     * @access public
     * @param  integer $id          project identifier
     * @param  array<mixed> $file   array of file properties to be uploaded
     * @throws Exception
     * @return void
     */
    public function uploadFile($id, array $file): void
    {
        if ($file['error'] == UPLOAD_ERR_OK && $file['size'] > 0) {
            // Check allowed file size.
            $this->helper->blobHelper->checkAllowedUploadSize($file['size']);

            $destination_filename = $this->generatePath($id, $file['name']);

            // $key = '/' . $destination_filename . '/' . $file['name'];
            // $key = BlobHelper::generateBlobKeyFilename($file['name'], $destination_filename);

            if ($this->isImage($file['name'])) {
                $this->generateThumbnailFromFile($file['tmp_name'], $destination_filename);
            }

            $this->objectStorage->moveUploadedFile($file['tmp_name'], $destination_filename);
            $this->create($id, $file['name'], $destination_filename, $file['size']);
        } else {
            if ($file['size'] === 0) {
                throw new Exception(e('File cannot be uploaded, file is empty.'));
            }
            throw new Exception(e('File upload error: ') . var_export($file['error'], true));
        }
    }

    /**
     * Handle screenshot uploads (base64 encoded content)
     *
     * @access public
     * @param  integer $id                  Task ID
     * @param  string  $originalFilename    Generated filename for screenshot
     * @param  string  $data                Base64 encoded image
     * @param  bool    $isEncoded           Whether the data is base64 encoded or not
     * @return bool|int
     */
    public function uploadContent($id, $originalFilename, $data, $isEncoded = true)
    {
        if ($isEncoded) {
            $data = base64_decode($data);
        }

        if (empty($data)) {
            $this->logger->error(__METHOD__ . ': Content upload with no data');
            throw new Exception(e('File cannot be uploaded, file is empty.'));
        }

        // Check allowed file size.
        $this->helper->blobHelper->checkAllowedUploadSize(strlen($data));

        $destinationFilename = $this->generatePath($id, $originalFilename);

        /* /tasks/1/e4be10d43b5845c993a3059eb0ba74e4009e39da/filename.ext */
        // $key = BlobHelper::generateBlobKeyFilename($originalFilename, $destinationFilename);

        $this->objectStorage->put($destinationFilename, $data);

        if ($this->isImage($originalFilename)) {
            $this->generateThumbnailFromData($destinationFilename, $data);
        }

        return $this->create(
            $id,
            $originalFilename,
            $destinationFilename,
            strlen($data)
        );
    }

    /**
     * Remove a file
     *
     * @access public
     * @param  integer  $file_id  File id from the local database
     * @return bool
     */
    public function remove($file_id)
    {
        $this->fireDestructionEvent($file_id);

        $file = $this->getById($file_id);

        // Only remove files from disk attached to a single task.
        $multiple_tasks_count = $this->db->table($this->getTable())->eq('path', $file['path'])->count();
        if ($multiple_tasks_count === 1) {
            $this->objectStorage->remove($file['path']);

            if ($file['is_image'] == 1) {
                $this->objectStorage->remove($this->getThumbnailPath($file['path']));
            }
        }

        return $this->db->table($this->getTable())->eq('id', $file['id'])->remove();
    }
}
