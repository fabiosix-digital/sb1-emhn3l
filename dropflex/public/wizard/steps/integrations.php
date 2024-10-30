<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wizard-step" id="step-integrations">
    <div class="step-header">
        <h1><?php _e('Conecte suas plataformas', 'dropflex'); ?></h1>
        <p class="step-description">
            <?php _e('Integre sua loja com as principais plataformas de e-commerce', 'dropflex'); ?>
        </p>
    </div>

    <div class="integrations-grid">
        <!-- Yampi -->
        <div class="integration-card">
            <div class="integration-header">
                <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/yampi-logo.png" alt="Yampi">
                <div class="integration-status" data-platform="yampi">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php _e('Não conectado', 'dropflex'); ?></span>
                </div>
            </div>

            <div class="integration-content">
                <h3><?php _e('Yampi', 'dropflex'); ?></h3>
                <p><?php _e('Conecte sua loja à Yampi para usar o checkout e importar produtos.', 'dropflex'); ?></p>

                <form class="integration-form yampi-form" style="display: none;">
                    <div class="form-field">
                        <label for="yampi_alias"><?php _e('Alias da Loja', 'dropflex'); ?></label>
                        <input type="text" id="yampi_alias" name="yampi_alias" required>
                        <p class="field-hint"><?php _e('Ex: minhaloja', 'dropflex'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="yampi_token"><?php _e('Token', 'dropflex'); ?></label>
                        <input type="text" id="yampi_token" name="yampi_token" required>
                    </div>

                    <div class="form-field">
                        <label for="yampi_secret"><?php _e('Chave Secreta', 'dropflex'); ?></label>
                        <input type="password" id="yampi_secret" name="yampi_secret" required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary btn-cancel">
                            <?php _e('Cancelar', 'dropflex'); ?>
                        </button>
                        <button type="submit" class="btn-primary">
                            <?php _e('Conectar', 'dropflex'); ?>
                        </button>
                    </div>
                </form>

                <button class="btn-connect" data-platform="yampi">
                    <?php _e('Conectar Yampi', 'dropflex'); ?>
                </button>
            </div>
        </div>

        <!-- Appmax -->
        <div class="integration-card">
            <div class="integration-header">
                <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/appmax-logo.png" alt="Appmax">
                <div class="integration-status" data-platform="appmax">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php _e('Não conectado', 'dropflex'); ?></span>
                </div>
            </div>

            <div class="integration-content">
                <h3><?php _e('Appmax', 'dropflex'); ?></h3>
                <p><?php _e('Integre com a Appmax para usar o checkout e gerenciar produtos.', 'dropflex'); ?></p>

                <form class="integration-form appmax-form" style="display: none;">
                    <div class="form-field">
                        <label for="appmax_token"><?php _e('Token', 'dropflex'); ?></label>
                        <input type="text" id="appmax_token" name="appmax_token" required>
                    </div>

                    <div class="form-field">
                        <label for="appmax_secret"><?php _e('Chave Secreta', 'dropflex'); ?></label>
                        <input type="password" id="appmax_secret" name="appmax_secret" required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary btn-cancel">
                            <?php _e('Cancelar', 'dropflex'); ?>
                        </button>
                        <button type="submit" class="btn-primary">
                            <?php _e('Conectar', 'dropflex'); ?>
                        </button>
                    </div>
                </form>

                <button class="btn-connect" data-platform="appmax">
                    <?php _e('Conectar Appmax', 'dropflex'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="wizard-navigation">
        <button class="wizard-btn btn-secondary" data-action="prev">
            <?php _e('Voltar', 'dropflex'); ?>
        </button>
        <button class="wizard-btn btn-primary" data-action="next">
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>

<style>
.integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.integration-card {
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.integration-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.integration-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--df-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.integration-header img {
    height: 32px;
}

.integration-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--df-text-light);
}

.status-dot.connected {
    background: var(--df-success);
}

.integration-content {
    padding: 1.5rem;
}

.integration-content h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
}

.integration-content p {
    color: var(--df-text-light);
    margin: 0 0 1.5rem;
}

.integration-form {
    margin-bottom: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn-connect {
    width: 100%;
    padding: 0.75rem;
    background: var(--df-primary);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-connect:hover {
    background: var(--df-primary-dark);
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--df-border);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Mostrar/ocultar formulários
    $('.btn-connect').on('click', function() {
        const platform = $(this).data('platform');
        $(this).hide();
        $(`.${platform}-form`).slideDown();
    });

    $('.btn-cancel').on('click', function() {
        const $form = $(this).closest('.integration-form');
        const $btn = $form.siblings('.btn-connect');
        $form.slideUp(() => {
            $btn.show();
        });
    });

    // Processar formulários
    $('.integration-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const platform = $form.hasClass('yampi-form') ? 'yampi' : 'appmax';
        
        const data = {
            action: 'dropflex_connect_platform',
            nonce: dropflexWizard.nonce,
            platform: platform,
            credentials: $form.serialize()
        };

        $.ajax({
            url: dropflexWizard.ajaxUrl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $form.find('button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    updateIntegrationStatus(platform, true);
                    $form.slideUp();
                } else {
                    alert(response.data);
                }
            },
            complete: function() {
                $form.find('button').prop('disabled', false);
            }
        });
    });

    function updateIntegrationStatus(platform, connected) {
        const $status = $(`.integration-status[data-platform="${platform}"]`);
        const $dot = $status.find('.status-dot');
        const $text = $status.find('.status-text');

        if (connected) {
            $dot.addClass('connected');
            $text.text('Conectado');
        } else {
            $dot.removeClass('connected');
            $text.text('Não conectado');
        }
    }
});
</script>