<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('dropflex_options');
        do_settings_sections('dropflex-settings');
        submit_button();
        ?>
    </form>
    
    <div class="dropflex-settings-tools">
        <h2><?php _e('Ferramentas', 'dropflex'); ?></h2>
        
        <div class="dropflex-tool-card">
            <h3><?php _e('Testar Conexão WHM', 'dropflex'); ?></h3>
            <p><?php _e('Verifica se as credenciais WHM estão funcionando corretamente.', 'dropflex'); ?></p>
            <button class="button button-secondary" id="dropflex-test-whm">
                <?php _e('Testar Conexão', 'dropflex'); ?>
            </button>
        </div>
        
        <div class="dropflex-tool-card">
            <h3><?php _e('Sincronizar Templates', 'dropflex'); ?></h3>
            <p><?php _e('Atualiza a lista de templates disponíveis.', 'dropflex'); ?></p>
            <button class="button button-secondary" id="dropflex-sync-templates">
                <?php _e('Sincronizar', 'dropflex'); ?>
            </button>
        </div>
        
        <div class="dropflex-tool-card">
            <h3><?php _e('Limpar Cache', 'dropflex'); ?></h3>
            <p><?php _e('Remove arquivos temporários e limpa o cache do plugin.', 'dropflex'); ?></p>
            <button class="button button-secondary" id="dropflex-clear-cache">
                <?php _e('Limpar', 'dropflex'); ?>
            </button>
        </div>
    </div>
</div>