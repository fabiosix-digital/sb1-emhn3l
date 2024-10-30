<?php
if (!defined('ABSPATH')) {
    exit;
}

$integration_manager = new DropFlex_Integration_Manager();
?>

<div class="dropflex-wizard-step" id="step-integrations">
    <h2><?php _e('Integrações', 'dropflex'); ?></h2>
    
    <div class="dropflex-integrations-grid">
        <!-- Yampi -->
        <div class="dropflex-integration-card">
            <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/yampi-logo.png" alt="Yampi">
            <h3><?php _e('Yampi', 'dropflex'); ?></h3>
            
            <?php 
            $yampi_guide = $integration_manager->get_integration_guide('yampi');
            if ($yampi_guide):
            ?>
                <div class="integration-guide">
                    <h4><?php echo esc_html($yampi_guide['title']); ?></h4>
                    
                    <div class="guide-steps">
                        <?php foreach ($yampi_guide['steps'] as $step): ?>
                            <p><?php echo esc_html($step); ?></p>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($yampi_guide['video_url']): ?>
                        <div class="guide-video">
                            <iframe width="100%" height="200" 
                                    src="<?php echo esc_url($yampi_guide['video_url']); ?>" 
                                    frameborder="0" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($yampi_guide['help_url']); ?>" 
                       target="_blank" class="guide-help">
                        <?php _e('Precisa de ajuda?', 'dropflex'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="integration-form">
                <div class="form-field">
                    <label for="yampi_alias"><?php _e('Alias da Loja', 'dropflex'); ?></label>
                    <input type="text" id="yampi_alias" name="yampi_alias">
                    <p class="field-help">
                        <?php _e('Ex: minhaloja', 'dropflex'); ?>
                    </p>
                </div>
                
                <div class="form-field">
                    <label for="yampi_token"><?php _e('Token', 'dropflex'); ?></label>
                    <input type="text" id="yampi_token" name="yampi_token">
                </div>
                
                <div class="form-field">
                    <label for="yampi_api_key"><?php _e('Chave API', 'dropflex'); ?></label>
                    <input type="text" id="yampi_api_key" name="yampi_api_key">
                </div>
                
                <button type="button" class="button button-primary test-connection" 
                        data-integration="yampi">
                    <?php _e('Testar Conexão', 'dropflex'); ?>
                </button>
            </div>
        </div>
        
        <!-- Appmax -->
        <div class="dropflex-integration-card">
            <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/appmax-logo.png" alt="Appmax">
            <h3><?php _e('Appmax', 'dropflex'); ?></h3>
            
            <?php 
            $appmax_guide = $integration_manager->get_integration_guide('appmax');
            if ($appmax_guide):
            ?>
                <div class="integration-guide">
                    <h4><?php echo esc_html($appmax_guide['title']); ?></h4>
                    
                    <div class="guide-steps">
                        <?php foreach ($appmax_guide['steps'] as $step): ?>
                            <p><?php echo esc_html($step); ?></p>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($appmax_guide['video_url']): ?>
                        <div class="guide-video">
                            <iframe width="100%" height="200" 
                                    src="<?php echo esc_url($appmax_guide['video_url']); ?>" 
                                    frameborder="0" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($appmax_guide['help_url']); ?>" 
                       target="_blank" class="guide-help">
                        <?php _e('Precisa de ajuda?', 'dropflex'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="integration-form">
                <div class="form-field">
                    <label for="appmax_api_key"><?php _e('Chave API', 'dropflex'); ?></label>
                    <input type="text" id="appmax_api_key" name="appmax_api_key">
                </div>
                
                <button type="button" class="button button-primary test-connection" 
                        data-integration="appmax">
                    <?php _e('Testar Conexão', 'dropflex'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div class="dropflex-wizard-navigation">
        <button class="button dropflex-prev-step">
            <?php _e('Voltar', 'dropflex'); ?>
        </button>
        <button class="button button-primary dropflex-next-step">
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>

<style>
.dropflex-integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.dropflex-integration-card {
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 0.5rem;
    padding: 2rem;
}

.dropflex-integration-card img {
    height: 48px;
    margin-bottom: 1rem;
}

.integration-guide {
    margin: 1.5rem 0;
    padding: 1.5rem;
    background: var(--df-background);
    border-radius: 0.375rem;
}

.guide-steps {
    margin: 1rem 0;
}

.guide-steps p {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
    position: relative;
}

.guide-steps p::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.5rem;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--df-primary);
}

.guide-video {
    margin: 1.5rem 0;
    border-radius: 0.375rem;
    overflow: hidden;
}

.guide-help {
    display: inline-block;
    margin-top: 1rem;
    color: var(--df-primary);
    text-decoration: none;
}

.integration-form {
    margin-top: 1.5rem;
}

.field-help {
    font-size: 0.875rem;
    color: var(--df-text-light);
    margin: 0.25rem 0 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.test-connection').on('click', function() {
        const $button = $(this);
        const integration = $button.data('integration');
        const $form = $button.closest('.integration-form');
        
        const credentials = {};
        $form.find('input').each(function() {
            credentials[this.name] = this.value;
        });
        
        $button.prop('disabled', true).text('Testando...');
        
        $.ajax({
            url: dropflexAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'dropflex_test_integration',
                nonce: dropflexAdmin.nonce,
                integration: integration,
                credentials: credentials
            },
            success: function(response) {
                if (response.success) {
                    alert('Conexão estabelecida com sucesso!');
                } else {
                    alert(response.data || 'Erro ao testar conexão');
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('Testar Conexão');
            }
        });
    });
    
    $('.dropflex-next-step').on('click', function() {
        const data = {
            yampi: {},
            appmax: {}
        };
        
        $('.integration-form').each(function() {
            const integration = $(this).find('.test-connection').data('integration');
            $(this).find('input').each(function() {
                data[integration][this.name] = this.value;
            });
        });
        
        $.ajax({
            url: dropflexAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'dropflex_save_integrations',
                nonce: dropflexAdmin.nonce,
                integrations: data
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.next_step;
                } else {
                    alert(response.data || 'Erro ao salvar integrações');
                }
            }
        });
    });
});
</script>