<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="dropflex-dashboard-grid">
        <div class="dropflex-card">
            <h2><?php _e('Sites Ativos', 'dropflex'); ?></h2>
            <div class="dropflex-card-content">
                <?php
                $site_manager = new DropFlex_Site_Manager();
                $total_sites = count($site_manager->get_user_sites());
                echo '<p class="dropflex-big-number">' . esc_html($total_sites) . '</p>';
                ?>
            </div>
        </div>
        
        <div class="dropflex-card">
            <h2><?php _e('Templates Disponíveis', 'dropflex'); ?></h2>
            <div class="dropflex-card-content">
                <?php
                $template_manager = new DropFlex_Template_Manager();
                $total_templates = count($template_manager->get_templates());
                echo '<p class="dropflex-big-number">' . esc_html($total_templates) . '</p>';
                ?>
            </div>
        </div>
        
        <div class="dropflex-card">
            <h2><?php _e('Status do Servidor', 'dropflex'); ?></h2>
            <div class="dropflex-card-content">
                <?php
                $whm = new DropFlex_WHM_Integration();
                $status = $whm->validate_credentials() ? 'online' : 'offline';
                echo '<p class="dropflex-status dropflex-status-' . esc_attr($status) . '">' . 
                    esc_html(ucfirst($status)) . 
                    '</p>';
                ?>
            </div>
        </div>
    </div>
    
    <div class="dropflex-recent-activity">
        <h2><?php _e('Atividade Recente', 'dropflex'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Data', 'dropflex'); ?></th>
                    <th><?php _e('Ação', 'dropflex'); ?></th>
                    <th><?php _e('Detalhes', 'dropflex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // TODO: Implementar log de atividades
                ?>
                <tr>
                    <td colspan="3"><?php _e('Nenhuma atividade recente.', 'dropflex'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>