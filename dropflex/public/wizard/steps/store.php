<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wizard-step" id="step-store">
    <div class="step-header">
        <h1><?php _e('Informe o nome do seu negócio', 'dropflex'); ?></h1>
        <p class="step-description">
            <?php _e('Digite um nome para a sua loja. Essa informação irá te ajudar a gerenciar todas as suas lojas.', 'dropflex'); ?>
        </p>
    </div>

    <form class="wizard-form" id="store-form">
        <div class="form-field">
            <label for="store_name"><?php _e('Nome do negócio', 'dropflex'); ?></label>
            <input type="text" id="store_name" name="store_name" required
                   placeholder="<?php esc_attr_e('Digite o nome do negócio', 'dropflex'); ?>">
        </div>

        <div class="form-field">
            <label for="store_url"><?php _e('URL da loja', 'dropflex'); ?></label>
            <div class="url-field">
                <span class="url-prefix">https://</span>
                <input type="text" id="store_url" name="store_url" required
                       placeholder="<?php esc_attr_e('minhaloja', 'dropflex'); ?>">
                <span class="url-suffix">.dropflex.com.br</span>
            </div>
            <p class="field-hint">
                <?php _e('Escolha um endereço único para sua loja', 'dropflex'); ?>
            </p>
        </div>

        <div class="form-field">
            <label for="timezone"><?php _e('Fuso horário', 'dropflex'); ?></label>
            <select id="timezone" name="timezone" required>
                <option value="America/Sao_Paulo">América/São Paulo</option>
                <option value="America/Manaus">América/Manaus</option>
                <option value="America/Belem">América/Belém</option>
                <!-- Adicionar mais fusos horários relevantes -->
            </select>
        </div>

        <div class="wizard-buttons">
            <button type="submit" class="wizard-btn btn-primary">
                <?php _e('Criar loja', 'dropflex'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.step-header {
    margin-bottom: 2rem;
}

.step-header h1 {
    font-size: 1.75rem;
    margin: 0 0 0.5rem;
}

.step-description {
    color: var(--df-text-light);
    margin: 0;
}

.url-field {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid var(--df-border);
    border-radius: 0.5rem;
    padding: 0 0.75rem;
}

.url-field input {
    border: none;
    padding: 0.75rem;
    flex: 1;
}

.url-field input:focus {
    outline: none;
}

.url-prefix,
.url-suffix {
    color: var(--df-text-light);
    font-size: 0.875rem;
}

.field-hint {
    margin: 0.5rem 0 0;
    font-size: 0.875rem;
    color: var(--df-text-light);
}
</style>

<script>
document.getElementById('store-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(dropflexWizard.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.next_step;
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao criar loja. Tente novamente.');
    });
});

// Validação da URL em tempo real
document.getElementById('store_url').addEventListener('input', function(e) {
    const url = this.value;
    if (url) {
        fetch(dropflexWizard.ajaxUrl, {
            method: 'POST',
            body: JSON.stringify({
                action: 'dropflex_check_url',
                url: url
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.available) {
                this.setCustomValidity('Esta URL já está em uso');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>