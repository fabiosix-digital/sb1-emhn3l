<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dropflex-wizard-step" id="step-settings">
    <h2><?php _e('Configurações da Loja', 'dropflex'); ?></h2>
    <p class="step-description">
        <?php _e('Configure as informações básicas da sua loja', 'dropflex'); ?>
    </p>

    <form id="store-settings-form" class="settings-form">
        <!-- Informações Gerais -->
        <div class="settings-section">
            <h3><?php _e('Informações Gerais', 'dropflex'); ?></h3>
            
            <div class="form-field">
                <label for="store_name"><?php _e('Nome da Loja', 'dropflex'); ?></label>
                <input type="text" id="store_name" name="store_name" required>
            </div>
            
            <div class="form-field">
                <label for="store_description"><?php _e('Descrição', 'dropflex'); ?></label>
                <textarea id="store_description" name="store_description" rows="3"></textarea>
            </div>
            
            <div class="form-field">
                <label for="store_email"><?php _e('E-mail de Contato', 'dropflex'); ?></label>
                <input type="email" id="store_email" name="store_email" required>
            </div>
            
            <div class="form-field">
                <label for="store_phone"><?php _e('Telefone', 'dropflex'); ?></label>
                <input type="tel" id="store_phone" name="store_phone">
            </div>
        </div>

        <!-- Endereço -->
        <div class="settings-section">
            <h3><?php _e('Endereço', 'dropflex'); ?></h3>
            
            <div class="form-field">
                <label for="store_address"><?php _e('Endereço', 'dropflex'); ?></label>
                <input type="text" id="store_address" name="store_address">
            </div>
            
            <div class="form-row">
                <div class="form-field">
                    <label for="store_city"><?php _e('Cidade', 'dropflex'); ?></label>
                    <input type="text" id="store_city" name="store_city">
                </div>
                
                <div class="form-field">
                    <label for="store_state"><?php _e('Estado', 'dropflex'); ?></label>
                    <input type="text" id="store_state" name="store_state">
                </div>
            </div>
            
            <div class="form-field">
                <label for="store_postal_code"><?php _e('CEP', 'dropflex'); ?></label>
                <input type="text" id="store_postal_code" name="store_postal_code">
            </div>
        </div>

        <!-- Redes Sociais -->
        <div class="settings-section">
            <h3><?php _e('Redes Sociais', 'dropflex'); ?></h3>
            
            <div class="form-field">
                <label for="social_facebook"><?php _e('Facebook', 'dropflex'); ?></label>
                <input type="url" id="social_facebook" name="social_facebook" placeholder="https://facebook.com/suapagina">
            </div>
            
            <div class="form-field">
                <label for="social_instagram"><?php _e('Instagram', 'dropflex'); ?></label>
                <input type="url" id="social_instagram" name="social_instagram" placeholder="https://instagram.com/suapagina">
            </div>
            
            <div class="form-field">
                <label for="social_whatsapp"><?php _e('WhatsApp', 'dropflex'); ?></label>
                <input type="tel" id="social_whatsapp" name="social_whatsapp" placeholder="(00) 00000-0000">
            </div>
        </div>

        <!-- SEO -->
        <div class="settings-section">
            <h3><?php _e('SEO', 'dropflex'); ?></h3>
            
            <div class="form-field">
                <label for="meta_title"><?php _e('Título da Página', 'dropflex'); ?></label>
                <input type="text" id="meta_title" name="meta_title">
                <p class="field-hint"><?php _e('Título que aparecerá na aba do navegador', 'dropflex'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="meta_description"><?php _e('Descrição', 'dropflex'); ?></label>
                <textarea id="meta_description" name="meta_description" rows="3"></textarea>
                <p class="field-hint"><?php _e('Descrição que aparecerá nos resultados de busca', 'dropflex'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="meta_keywords"><?php _e('Palavras-chave', 'dropflex'); ?></label>
                <input type="text" id="meta_keywords" name="meta_keywords">
                <p class="field-hint"><?php _e('Separe as palavras-chave por vírgula', 'dropflex'); ?></p>
            </div>
        </div>
    </form>

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
.settings-form {
    max-width: 800px;
    margin: 0 auto;
}

.settings-section {
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.settings-section h3 {
    margin: 0 0 1.5rem;
    font-size: 1.25rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.field-hint {
    font-size: 0.875rem;
    color: var(--df-text-light);
    margin: 0.25rem 0 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Máscara para telefone
    $('#store_phone, #social_whatsapp').mask('(00) 00000-0000');
    
    // Máscara para CEP
    $('#store_postal_code').mask('00000-000');
    
    // Buscar endereço pelo CEP
    $('#store_postal_code').on('blur', function() {
        const cep = $(this).val().replace(/\D/g, '');
        
        if (cep.length === 8) {
            $.get(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                if (!data.erro) {
                    $('#store_address').val(data.logradouro);
                    $('#store_city').val(data.localidade);
                    $('#store_state').val(data.uf);
                }
            });
        }
    });
    
    // Salvar configurações
    $('.dropflex-next-step').on('click', function() {
        const formData = new FormData($('#store-settings-form')[0]);
        formData.append('action', 'dropflex_save_settings');
        formData.append('nonce', dropflexAdmin.nonce);
        
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