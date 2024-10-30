<?php
if (!defined('ABSPATH')) {
    exit;
}

$checkout_manager = new DropFlex_Checkout_Manager();
$available_checkouts = $checkout_manager->get_available_checkouts();
?>

<div class="dropflex-wizard-step" id="step-checkout">
    <h2><?php _e('Configurar Checkout', 'dropflex'); ?></h2>
    <p class="step-description">
        <?php _e('Escolha e configure o checkout que será utilizado em sua loja.', 'dropflex'); ?>
    </p>
    
    <div class="dropflex-checkout-options">
        <?php foreach ($available_checkouts as $id => $checkout): ?>
            <div class="checkout-option" data-checkout="<?php echo esc_attr($id); ?>">
                <div class="checkout-header">
                    <img src="<?php echo esc_url($checkout['icon']); ?>" 
                         alt="<?php echo esc_attr($checkout['name']); ?>">
                    <h3><?php echo esc_html($checkout['name']); ?></h3>
                </div>
                
                <p class="checkout-description">
                    <?php echo esc_html($checkout['description']); ?>
                </p>
                
                <button type="button" class="button button-primary select-checkout">
                    <?php _e('Selecionar', 'dropflex'); ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Configuração Yampi -->
    <div class="checkout-config" id="yampi-config" style="display: none;">
        <h3><?php _e('Configurar Yampi Checkout', 'dropflex'); ?></h3>
        
        <form class="checkout-form">
            <div class="form-field">
                <label for="yampi_alias"><?php _e('Alias da Loja', 'dropflex'); ?></label>
                <input type="text" id="yampi_alias" name="yampi_alias" required>
                <p class="field-help">
                    <?php _e('O alias é o identificador único da sua loja na Yampi', 'dropflex'); ?>
                </p>
            </div>
            
            <div class="form-field">
                <label for="yampi_token"><?php _e('Token', 'dropflex'); ?></label>
                <input type="text" id="yampi_token" name="yampi_token" required>
            </div>
            
            <div class="form-field">
                <label for="yampi_api_key"><?php _e('Chave API', 'dropflex'); ?></label>
                <input type="text" id="yampi_api_key" name="yampi_api_key" required>
            </div>
            
            <button type="button" class="button button-secondary test-connection">
                <?php _e('Testar Conexão', 'dropflex'); ?>
            </button>
        </form>
    </div>
    
    <!-- Configuração Appmax -->
    <div class="checkout-config" id="appmax-config" style="display: none;">
        <h3><?php _e('Configurar Appmax Checkout', 'dropflex'); ?></h3>
        
        <form class="checkout-form">
            <div class="form-field">
                <label for="appmax_api_key"><?php _e('Chave API', 'dropflex'); ?></label>
                <input type="text" id="appmax_api_key" name="appmax_api_key" required>
            </div>
            
            <button type="button" class="button button-secondary test-connection">
                <?php _e('Testar Conexão', 'dropflex'); ?>
            </button>
        </form>
    </div>
    
    <div class="dropflex-wizard-navigation">
        <button class="button dropflex-prev-step">
            <?php _e('Voltar', 'dropflex'); ?>
        </button>
        <button class="button button-primary dropflex-next-step" disabled>
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>

<style>
.dropflex-checkout-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.checkout-option {
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.checkout-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.checkout-option.selected {
    border-color: var(--df-primary);
    background: var(--df-background);
}

.checkout-header {
    margin-bottom: 1rem;
}

.checkout-header img {
    height: 48px;
    margin-bottom: 1rem;
}

.checkout-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.checkout-description {
    color: var(--df-text-light);
    margin-bottom: 1.5rem;
}

.checkout-config {
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 0.5rem;
    padding: 2rem;
    margin-top: 2rem;
}

.checkout-form {
    max-width: 500px;
}

.field-help {
    font-size: 0.875rem;
    color: var(--df-text-light);
    margin: 0.25rem 0 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Seleção do checkout
    $('.select-checkout').on('click', function() {
        const $option = $(this).closest('.checkout-option');
        const checkoutType = $option.data('checkout');
        
        $('.checkout-option').removeClass('selected');
        $option.addClass('selected');
        
        $('.checkout-config').hide();
        $(`#${checkoutType}-config`).show();
        
        $('.dropflex-next-step').prop('disabled', false);
    });
    
    // Teste de conexão
    $('.test-connection').on('click', function() {
        const $form = $(this).closest('form');
        const formData = new FormData($form[0]);
        formData.append('action', 'dropflex_test_checkout');
        formData.append('nonce', dropflexAdmin.nonce);
        
        $.ajax({
            url: dropflexAdmin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Conexão estabelecida com sucesso!');
                } else {
                    alert(response.data || 'Erro ao testar conexão');
                }
            }
        });
    });
    
    // Salvar configurações
    $('.dropflex-next-step').on('click', function() {
        const $selected = $('.checkout-option.selected');
        if (!$selected.length) return;
        
        const checkoutType = $selected.data('checkout');
        const $form = $(`#${checkoutType}-config form`);
        const formData = new FormData($form[0]);
        
        formData.append('action', 'dropflex_save_checkout');
        formData.append('nonce', dropflexAdmin.nonce);
        formData.append('checkout_type', checkoutType);
        
        $.ajax({
            url: dropflexAdmin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.next_step;
                } else {
                    alert(response.data || 'Erro ao salvar configurações');
                }
            }
        });
    });
});
</script>