<?php
declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Controller;

use Kanboard\Controller\TaskFileController;
use Kanboard\Core\ObjectStorage\ObjectStorageException;

/**
 * Project File Controller
 *
 * @package  Kanboard\Controller
 */
class BlobTaskFileController extends TaskFileController
{
    /**
     * Save uploaded files
     *
     * @access public
     */
    public function save()
    {
        $this->checkReusableCSRFParam();
        $task = $this->getTask();

        try {
            $result = $this->taskFileModel->uploadFiles($task['id'], $this->request->getFileInfo('files'));
            if ($this->request->isAjax()) {
                $this->response->json(array('message' => 'OK'));
            } else {
                $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])), true);
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
     * Screenshot
     *
     * @access public
     */
    public function screenshot()
    {
        $task = $this->getTask();

        if ($this->request->isPost()) {
            try {
                $result = $this->taskFileModel->uploadScreenshot($task['id'], $this->request->getValue('screenshot'));
                $this->flash->success(t('Screenshot uploaded successfully.'));
                return $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])), true);
            } catch (\Exception | ObjectStorageException $e) {
                if ($this->request->isAjax()) {
                    $this->response->json(array('message' => $e->getMessage()), 500);
                } else {
                    $this->flash->failure($result);
                    return $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])), true);
                }
            }
        }

        return $this->response->html($this->template->render('task_file/screenshot', array(
            'task' => $task,
        )));
    }

    /**
     * Remove a file
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $task = $this->getTask();
        $file = $this->taskFileModel->getById($this->request->getIntegerParam('file_id'));

        if ($file['task_id'] == $task['id']) {
            try {
                $this->taskFileModel->remove($file['id']);
                $this->flash->success('File removed successfully.');
            } catch (\Exception | ObjectStorageException $e) {
                $this->flash->failure('Unable to remove this file. ' . $e->getMessage());
            }
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])));
    }
}
