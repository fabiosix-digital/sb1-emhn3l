<?php
if (!defined('ABSPATH')) {
    exit;
}

$wizard = new DropFlex_Wizard();
$current_step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'store';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Criar sua Loja - DropFlex', 'dropflex'); ?></title>
    <link rel="stylesheet" href="<?php echo DROPFLEX_ASSETS_URL; ?>css/wizard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="dropflex-wizard-page">
    <!-- Header -->
    <header class="wizard-header">
        <div class="wizard-container">
            <div class="wizard-logo">
                <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/logo.svg" alt="DropFlex">
            </div>
            <div class="wizard-user">
                <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                <a href="<?php echo wp_logout_url(); ?>" class="logout-link">
                    <?php _e('Sair', 'dropflex'); ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Progress Sidebar -->
    <div class="wizard-layout">
        <aside class="wizard-sidebar">
            <div class="wizard-progress">
                <h2><?php _e('Seu progresso', 'dropflex'); ?></h2>
                <ul class="progress-steps">
                    <li class="<?php echo $current_step === 'store' ? 'active' : ''; ?>">
                        <?php _e('Cadastro da loja', 'dropflex'); ?>
                    </li>
                    <li class="<?php echo $current_step === 'platforms' ? 'active' : ''; ?>">
                        <?php _e('Integrar plataformas', 'dropflex'); ?>
                    </li>
                    <li class="<?php echo $current_step === 'template' ? 'active' : ''; ?>">
                        <?php _e('Escolher template', 'dropflex'); ?>
                    </li>
                    <li class="<?php echo $current_step === 'customize' ? 'active' : ''; ?>">
                        <?php _e('Personalizar', 'dropflex'); ?>
                    </li>
                    <li class="<?php echo $current_step === 'settings' ? 'active' : ''; ?>">
                        <?php _e('Configurações gerais', 'dropflex'); ?>
                    </li>
                    <li class="<?php echo $current_step === 'finish' ? 'active' : ''; ?>">
                        <?php _e('Finalizar', 'dropflex'); ?>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="wizard-content">
            <?php $wizard->render_step($current_step); ?>
        </main>
    </div>

    <script src="<?php echo DROPFLEX_ASSETS_URL; ?>js/wizard.js"></script>
</body>
</html>