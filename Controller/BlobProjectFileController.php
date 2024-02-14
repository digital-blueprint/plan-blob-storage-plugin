<?php
declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Controller;

use Kanboard\Controller\ProjectFileController;
use Kanboard\Core\ObjectStorage\ObjectStorageException;

/**
 * Project File Controller
 *
 * @package  Kanboard\Controller
 */
class BlobProjectFileController extends ProjectFileController
{
    /**
     * Save uploaded files
     *
     * @access public
     */
    public function save()
    {
        $this->checkReusableCSRFParam();
        $project = $this->getProject();

        try {
            $result = $this->projectFileModel->uploadFiles($project['id'], $this->request->getFileInfo('files'));
            if ($this->request->isAjax()) {
                $this->response->json(array('message' => 'OK'));
            } else {
                $this->response->redirect($this->helper->url->to('ProjectOverviewController', 'show', array('project_id' => $project['id'])), true);
            }
        } catch (\Exception | ObjectStorageException $e) {
            if ($this->request->isAjax()) {
                $this->response->json(array('message' => $e->getMessage()), 500);
            } else {
                $this->flash->failure($e->getMessage());
            }
        }
    }

    /**
     * Remove a file
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $file = $this->projectFileModel->getById($this->request->getIntegerParam('file_id'));

        try {
            $this->projectFileModel->remove($file['id']);
            $this->flash->success('File removed successfully.');
        } catch (\Exception | ObjectStorageException $e) {
            $this->flash->failure('Unable to remove this file. ' . $e->getMessage());
        }

        $this->response->redirect($this->helper->url->to('ProjectOverviewController', 'show', array('project_id' => $project['id'])));
    }
}
