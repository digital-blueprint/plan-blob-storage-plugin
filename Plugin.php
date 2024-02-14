<?php

/**
 * @file
 * Plugin to store files in Blob.
 */

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\BlobStorage\BlobObjectStorage;
use Kanboard\Plugin\BlobStorage\Model\BlobTaskFileModel;
use Kanboard\Plugin\BlobStorage\Model\BlobProjectFileModel;

class Plugin extends Base
{
    public function initialize(): void
    {
        $this->container['objectStorage'] = function () {
            return new BlobObjectStorage(
                $this->getConfigBlobKey(),
                $this->getConfigBlobBucketId(),
                $this->getConfigBlobApiHost()
            );
        };

        //HELPER
        $this->helper->register('blobHelper', '\Kanboard\Plugin\BlobStorage\Helper\BlobHelper');

        // Register file models.
        $this->container['taskFileModel'] =  $this->container->factory(
            function ($c) {
                return new BlobTaskFileModel($c);
            }
        );
        $this->container['projectFileModel'] =  $this->container->factory(
            function ($c) {
                return new BlobProjectFileModel($c);
            }
        );

        // Config form.
        $this->template->hook->attach('template:config:integrations', 'BlobStorage:config');

        // Add custom CSS.
        $this->hook->on('template:layout:css', array('template' => 'plugins/BlobStorage/Assets/css/blob-storage.css'));

        // Override file upload and delete forms.
        $this->template->setTemplateOverride('project_file/create', 'BlobStorage:project_file/create');
        $this->template->setTemplateOverride('project_file/remove', 'BlobStorage:project_file/remove');
        $this->template->setTemplateOverride('task_file/create', 'BlobStorage:task_file/create');
        $this->template->setTemplateOverride('task_file/remove', 'BlobStorage:task_file/remove');
    }

    public function getClasses()
    {
        return array(
            'Plugin\BlobStorage\Model' => array(
                'BlobProjectFileModel',
                'BlobTaskFileModel',
            )
        );
    }

    public function isConfigured(): bool
    {
        if (!$this->getConfigBlobKey() || !$this->getConfigBlobBucketId() || !$this->getConfigBlobApiHost()) {
            $this->logger->info('Plugin Blob Storage not configured!');
            return false;
        }

        return true;
    }

    public function getPluginName()
    {
        return 'Blob Object Storage';
    }

    public function getPluginDescription()
    {
        return 'Use Blob as a storage backend of plan.';
    }

    public function getPluginAuthor()
    {
        return 'David Zsuffa';
    }

    public function getPluginVersion()
    {
        return '0.1';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/digital-blueprint/plan-blob-storage-plugin';
    }

    /**
     * Get config blob key
     *
     * @access private
     *
     * @return string
     */
    private function getConfigBlobKey()
    {
        if (defined('BLOB_KEY') && BLOB_KEY) {
            return BLOB_KEY;
        }

        return $this->configModel->get('blob_key');
    }

    /**
     * Get config blob bucket id
     *
     * @access private
     *
     * @return string
     */
    private function getConfigBlobBucketId()
    {
        if (defined('BLOB_BUCKET_ID') && BLOB_BUCKET_ID) {
            return BLOB_BUCKET_ID;
        }

        return $this->configModel->get('blob_bucket_id');
    }

    /**
     * Get config blob api hostname
     *
     * @access private
     *
     * @return string
     */
    private function getConfigBlobApiHost()
    {
        if (defined('BLOB_API_HOST') && BLOB_API_HOST) {
            return BLOB_API_HOST;
        }

        return $this->configModel->get('blob_api_host');
    }
}
