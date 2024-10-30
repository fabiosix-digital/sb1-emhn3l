<?php
if (!defined('ABSPATH')) {
    exit;
}

$wizard = new DropFlex_Wizard();
$current_step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'welcome';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Criar sua Loja - DropFlex', 'dropflex'); ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo DROPFLEX_ASSETS_URL; ?>css/wizard.css">
    <link rel="stylesheet" href="<?php echo DROPFLEX_ASSETS_URL; ?>css/wizard-steps.css">
</head>
<body class="dropflex-wizard">
    <!-- Header -->
    <header class="wizard-header">
        <div class="wizard-container">
            <div class="wizard-logo">
                <img src="<?php echo DROPFLEX_ASSETS_URL; ?>images/logo.svg" alt="DropFlex">
            </div>
            
            <div class="wizard-user">
                <div class="user-info">
                    <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                    <span class="user-plan"><?php _e('Plano Premium', 'dropflex'); ?></span>
                </div>
                <a href="<?php echo wp_logout_url(); ?>" class="logout-link">
                    <?php _e('Sair', 'dropflex'); ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="wizard-main">
        <div class="wizard-container">
            <!-- Progress Bar -->
            <div class="wizard-progress">
                <div class="progress-steps">
                    <?php
                    $steps = $wizard->get_steps();
                    $current_step_index = array_search($current_step, array_keys($steps));
                    
                    foreach ($steps as $step_id => $step):
                        $step_index = array_search($step_id, array_keys($steps));
                        $is_active = $step_id === $current_step;
                        $is_completed = $step_index < $current_step_index;
                        $class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                    ?>
                        <div class="progress-step <?php echo $class; ?>">
                            <div class="step-number">
                                <?php if ($is_completed): ?>
                                    <span class="dashicons dashicons-yes"></span>
                                <?php else: ?>
                                    <?php echo $step_index + 1; ?>
                                <?php endif; ?>
                            </div>
                            <div class="step-info">
                                <span class="step-title"><?php echo $step['title']; ?></span>
                                <span class="step-description"><?php echo $step['description']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step Content -->
            <div class="wizard-content">
                <?php $wizard->render_step($current_step); ?>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?php echo DROPFLEX_ASSETS_URL; ?>js/wizard.js"></script>
    <script>
        const dropflexWizard = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('dropflex_wizard'); ?>',
            currentStep: '<?php echo $current_step; ?>',
            nextStep: '<?php echo $wizard->get_next_step($current_step); ?>',
            prevStep: '<?php echo $wizard->get_prev_step($current_step); ?>'
        };
    </script>
</body>
</html>