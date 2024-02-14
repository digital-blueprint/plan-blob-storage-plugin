<h3>
    <i class="fa fa-file fa-fw" aria-hidden="true"></i>
    Blob File Storage
</h3>
<div class="panel">
    <?php echo $this->form->label(t('Blob key'), 'blob_key') ?>
    <?php echo $this->form->text('blob_key', $values) ?>

    <?php echo $this->form->label(t('Blob bucket ID'), 'blob_bucket_id') ?>
    <?php echo $this->form->text('blob_bucket_id', $values) ?>

    <?php echo $this->form->label(t('Blob API host'), 'blob_api_host') ?>
    <?php echo $this->form->text('blob_api_host', $values) ?>

    <p class="form-help"><a href="https://github.com/digital-blueprint/relay-blob-library#usage" target="_blank"><?php echo t('Help on Blob Library configuration') ?></a></p>

    <div class="form-actions">
        <button class="btn btn-blue"><?php echo t('Save') ?></button>
    </div>
</div>