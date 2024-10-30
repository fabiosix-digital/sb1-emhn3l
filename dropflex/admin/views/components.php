<?php
if (!defined('ABSPATH')) {
    exit;
}

$component_manager = new DropFlex_Component_Manager();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'headers';
?>

<div class="wrap dropflex-components">
    <h1 class="wp-heading-inline"><?php _e('Componentes', 'dropflex'); ?></h1>
    <a href="#" class="page-title-action" id="dropflex-add-component">
        <?php _e('Adicionar Novo', 'dropflex'); ?>
    </a>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=dropflex-components&tab=headers" 
           class="nav-tab <?php echo $active_tab === 'headers' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Cabeçalhos', 'dropflex'); ?>
        </a>
        <a href="?page=dropflex-components&tab=footers" 
           class="nav-tab <?php echo $active_tab === 'footers' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Rodapés', 'dropflex'); ?>
        </a>
        <a href="?page=dropflex-components&tab=home" 
           class="nav-tab <?php echo $active_tab === 'home' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Home', 'dropflex'); ?>
        </a>
        <a href="?page=dropflex-components&tab=about" 
           class="nav-tab <?php echo $active_tab === 'about' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Sobre', 'dropflex'); ?>
        </a>
        <a href="?page=dropflex-components&tab=contact" 
           class="nav-tab <?php echo $active_tab === 'contact' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Contato', 'dropflex'); ?>
        </a>
        <a href="?page=dropflex-components&tab=colors" 
           class="nav-tab <?php echo $active_tab === 'colors' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Cores', 'dropflex'); ?>
        </a>
    </nav>
    
    <div class="dropflex-components-grid">
        <?php
        if ($active_tab === 'colors') {
            $color_schemes = $component_manager->get_color_schemes();
            foreach ($color_schemes as $id => $scheme):
            ?>
                <div class="dropflex-color-scheme-card" data-scheme-id="<?php echo esc_attr($id); ?>">
                    <h3><?php echo esc_html($scheme['name']); ?></h3>
                    <div class="color-preview">
                        <?php foreach ($scheme['colors'] as $name => $color): ?>
                            <div class="color-swatch" style="background-color: <?php echo esc_attr($color); ?>">
                                <span><?php echo esc_html($name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="scheme-actions">
                        <button class="button button-primary edit-scheme">
                            <?php _e('Editar', 'dropflex'); ?>
                        </button>
                        <button class="button button-secondary duplicate-scheme">
                            <?php _e('Duplicar', 'dropflex'); ?>
                        </button>
                    </div>
                </div>
            <?php
            endforeach;
        } else {
            $components = $component_manager->get_components($active_tab);
            foreach ($components as $component):
            ?>
                <div class="dropflex-component-card" data-component-id="<?php echo esc_attr($component['id']); ?>">
                    <div class="component-preview">
                        <?php if (isset($component['preview_image'])): ?>
                            <img src="<?php echo esc_url($component['preview_image']); ?>" 
                                 alt="<?php echo esc_attr($component['name']); ?>">
                        <?php else: ?>
                            <div class="preview-placeholder">
                                <span class="dashicons dashicons-layout"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="component-info">
                        <h3><?php echo esc_html($component['name']); ?></h3>
                        <?php if (isset($component['description'])): ?>
                            <p><?php echo esc_html($component['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="component-actions">
                        <button class="button button-secondary preview-component">
                            <?php _e('Visualizar', 'dropflex'); ?>
                        </button>
                        <button class="button button-primary edit-component">
                            <?php _e('Editar', 'dropflex'); ?>
                        </button>
                        <button class="button button-link-delete delete-component">
                            <?php _e('Excluir', 'dropflex'); ?>
                        </button>
                    </div>
                </div>
            <?php
            endforeach;
        }
        ?>
    </div>
</div>

<!-- Modal de Edição de Componente -->
<div id="dropflex-component-modal" class="dropflex-modal">
    <div class="dropflex-modal-content dropflex-modal-large">
        <span class="dropflex-modal-close">&times;</span>
        <h2 id="modal-title"><?php _e('Editar Componente', 'dropflex'); ?></h2>
        
        <form id="component-form">
            <input type="hidden" name="component_id" id="component_id">
            <input type="hidden" name="component_type" id="component_type">
            
            <div class="form-field">
                <label for="component_name"><?php _e('Nome', 'dropflex'); ?></label>
                <input type="text" id="component_name" name="component_name" required>
            </div>
            
            <div class="form-field">
                <label for="component_description"><?php _e('Descrição', 'dropflex'); ?></label>
                <textarea id="component_description" name="component_description" rows="3"></textarea>
            </div>
            
            <div class="form-field">
                <label for="component_content"><?php _e('Conteúdo HTML', 'dropflex'); ?></label>
                <textarea id="component_content" name="component_content" rows="15" class="code"></textarea>
            </div>
            
            <div class="form-field">
                <label for="component_css"><?php _e('CSS Personalizado', 'dropflex'); ?></label>
                <textarea id="component_css" name="component_css" rows="10" class="code"></textarea>
            </div>
            
            <div class="form-field">
                <label for="preview_image"><?php _e('Imagem de Preview', 'dropflex'); ?></label>
                <div class="preview-upload">
                    <input type="hidden" id="preview_image" name="preview_image">
                    <div id="preview-image-container"></div>
                    <button type="button" class="button" id="upload-preview">
                        <?php _e('Upload', 'dropflex'); ?>
                    </button>
                </div>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Salvar', 'dropflex'); ?>
                </button>
            </div>
        </form>
    </div>
</div>