<div class="page-header">
    <h2><?= t('Attach a document to PROJECT') ?></h2>
</div>

<?= $this->app->component('file-upload', array(
    'csrf'              => $this->app->getToken()->getReusableCSRFToken(),
    'maxSize'           => $max_size,
    'url'               => $this->url->to('BlobProjectFileController', 'save', array('project_id' => $project['id'], 'plugin' => 'BlobStorage')),
    'labelDropzone'     => t('Drag and drop your files here'),
    'labelOr'           => t('or'),
    'labelChooseFiles'  => t('BLOB files'),
    'labelOversize'     => $max_size > 0 ? t('The maximum allowed file size is %sB.', $this->text->bytes($max_size)) : null,
    'labelSuccess'      => t('All files have been uploaded successfully.'),
    'labelCloseSuccess' => t('Close this window'),
    'labelUploadError'  => t('Unable to upload this file.'),
)) ?>

<?= $this->modal->submitButtons(array(
    'submitLabel' => t('Upload PROJECT files'),
    'disabled'    => true,
)) ?>
