<h3>
    <i class="fa fa-file fa-fw" aria-hidden="true"></i>
    Blob File Storage
</h3>
<div class="panel blob-config-panel" id="blob-config-panel">
    <formgroup class="form-group" id="blob-settings">
        <?php echo $this->form->label(t('Blob key'), 'blob_key') ?>
        <?php echo $this->form->text('blob_key', $values) ?>

        <?php echo $this->form->label(t('Blob bucket ID'), 'blob_bucket_id') ?>
        <?php echo $this->form->text('blob_bucket_id', $values) ?>

        <?php echo $this->form->label(t('Blob API host'), 'blob_api_host') ?>
        <?php echo $this->form->text('blob_api_host', $values) ?>

        <p class="form-help"><a href="https://github.com/digital-blueprint/relay-blob-library#usage" target="_blank"><?php echo t('Help on Blob Library configuration') ?></a></p>
    </formgroup>

    <formgroup class="form-group" id="mimetype-settings">
        <?php echo $this->form->label(t('Allowed MIME types to upload'), 'blob_allowed_mime_types') ?>
        <?php echo $this->form->textarea('blob_allowed_mime_types', $values) ?>
        <p class="form-help">Format: one MIME type per line</p>
        <p class="form-help">text/plain,<br>image/jpeg<br>application/pdf</p>
        <p class="form-help"><a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types" target="_blank"><?php echo t('Help on MIME types') ?></a></p>
    </formgroup>

    <formgroup class="form-group" id="upload-settings">
        <?php echo $this->form->label(t('Maximum file upload size in MB'), 'blob_allowed_max_file_upload_size') ?>
        <?php echo $this->form->input('number', 'blob_allowed_max_file_upload_size', $values, [], ['required', 'min="1"', 'max="100"']) ?>
    </formgroup>

    <div class="form-actions">
        <button class="btn btn-blue"><?php echo t('Save') ?></button>
    </div>
</div>