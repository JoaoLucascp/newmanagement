<?php

/**
 * Newmanagement - Página de configuração do plugin
 * Referenciada em setup.php via $PLUGIN_HOOKS['config_page']
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('config', UPDATE);

Html::header(
    __('Configuração', 'newmanagement'),
    '',
    'config',
    'plugins'
);
?>

<div class="container-fluid mt-4">
  <div class="card">
    <div class="card-header">
      <h3><?php echo __('Newmanagement — Configurações', 'newmanagement'); ?></h3>
    </div>
    <div class="card-body">
      <p class="text-muted">
        <?php echo __('Versão', 'newmanagement'); ?>:
        <strong><?php echo PLUGIN_NEWMANAGEMENT_VERSION; ?></strong>
      </p>
      <p class="text-muted">
        <?php echo __('GLPI compatível', 'newmanagement'); ?>:
        <?php echo PLUGIN_NEWMANAGEMENT_MIN_GLPI_VERSION; ?>
        &mdash;
        <?php echo PLUGIN_NEWMANAGEMENT_MAX_GLPI_VERSION; ?>
      </p>
      <hr>
      <p>
        <?php echo __('Nenhuma configuração adicional necessária. O plugin está pronto para uso.', 'newmanagement'); ?>
      </p>
    </div>
  </div>
</div>

<?php
Html::footer();
