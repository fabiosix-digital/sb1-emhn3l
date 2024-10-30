<?php
if (!defined('ABSPATH')) {
    exit;
}

$plans = new DropFlex_Plans();
$all_plans = $plans->get_plans();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Planos', 'dropflex'); ?></h1>
    <a href="#" class="page-title-action" id="dropflex-add-plan"><?php _e('Adicionar Novo', 'dropflex'); ?></a>
    
    <div class="dropflex-plans-grid">
        <?php foreach ($all_plans as $plan): ?>
            <div class="dropflex-plan-card" data-plan-id="<?php echo esc_attr($plan->id); ?>">
                <h2><?php echo esc_html($plan->name); ?></h2>
                <div class="dropflex-plan-price">
                    <span class="price">R$ <?php echo number_format($plan->price, 2, ',', '.'); ?></span>
                    <span class="period">/mês</span>
                </div>
                
                <div class="dropflex-plan-description">
                    <?php echo wp_kses_post($plan->description); ?>
                </div>
                
                <div class="dropflex-plan-features">
                    <?php
                    $features = json_decode($plan->features, true);
                    if ($features):
                    ?>
                        <ul>
                            <?php foreach ($features as $feature): ?>
                                <li><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="dropflex-plan-actions">
                    <button class="button button-primary dropflex-edit-plan">
                        <?php _e('Editar', 'dropflex'); ?>
                    </button>
                    <button class="button button-secondary dropflex-delete-plan">
                        <?php _e('Excluir', 'dropflex'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de Edição/Criação de Plano -->
<div id="dropflex-plan-modal" class="dropflex-modal" style="display: none;">
    <div class="dropflex-modal-content">
        <span class="dropflex-modal-close">&times;</span>
        <h2 id="dropflex-modal-title"><?php _e('Adicionar Plano', 'dropflex'); ?></h2>
        
        <form id="dropflex-plan-form">
            <input type="hidden" id="plan_id" name="plan_id" value="">
            
            <div class="form-field">
                <label for="plan_name"><?php _e('Nome do Plano', 'dropflex'); ?></label>
                <input type="text" id="plan_name" name="plan_name" required>
            </div>
            
            <div class="form-field">
                <label for="plan_description"><?php _e('Descrição', 'dropflex'); ?></label>
                <textarea id="plan_description" name="plan_description" rows="4"></textarea>
            </div>
            
            <div class="form-field">
                <label for="plan_price"><?php _e('Preço Mensal (R$)', 'dropflex'); ?></label>
                <input type="number" id="plan_price" name="plan_price" step="0.01" required>
            </div>
            
            <div class="form-field">
                <label><?php _e('Recursos', 'dropflex'); ?></label>
                <div id="plan_features">
                    <div class="feature-item">
                        <input type="text" name="features[]" class="feature-input">
                        <button type="button" class="button remove-feature">&times;</button>
                    </div>
                </div>
                <button type="button" class="button" id="add_feature">
                    <?php _e('Adicionar Recurso', 'dropflex'); ?>
                </button>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Salvar', 'dropflex'); ?>
                </button>
            </div>
        </form>
    </div>
</div>