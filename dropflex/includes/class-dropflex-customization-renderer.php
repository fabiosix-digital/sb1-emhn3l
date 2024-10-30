<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Customization_Renderer {
    private $customization;
    
    public function __construct() {
        $this->customization = new DropFlex_Customization_Manager();
    }
    
    public function render_styles($site_id) {
        $customization = $this->customization->get_customization($site_id);
        if (!$customization) {
            return '';
        }
        
        $css = [];
        
        // Cores
        $css[] = $this->render_colors($customization['colors']);
        
        // Tipografia
        $css[] = $this->render_typography($customization['typography']);
        
        // Layout
        $css[] = $this->render_layout($customization['layout']);
        
        // Elementos personalizados
        $css[] = $this->render_custom_elements($customization['custom_elements']);
        
        return implode("\n", array_filter($css));
    }
    
    private function render_colors($colors) {
        if (!$colors) return '';
        
        $css = ':root {';
        
        foreach ($colors as $name => $value) {
            $css .= sprintf(
                '--df-%s: %s;',
                esc_attr($name),
                esc_attr($value)
            );
        }
        
        $css .= '}';
        
        return $css;
    }
    
    private function render_typography($typography) {
        if (!$typography) return '';
        
        $css = [];
        
        foreach ($typography as $element => $styles) {
            $selector = $this->get_typography_selector($element);
            
            $css[] = sprintf(
                '%s {
                    font-family: %s;
                    font-size: %s;
                    font-weight: %s;
                    line-height: %s;
                    letter-spacing: %s;
                }',
                $selector,
                esc_attr($styles['font-family']),
                esc_attr($styles['font-size']),
                esc_attr($styles['font-weight']),
                esc_attr($styles['line-height']),
                esc_attr($styles['letter-spacing'])
            );
        }
        
        return implode("\n", $css);
    }
    
    private function get_typography_selector($element) {
        $selectors = [
            'body' => 'body',
            'headings' => 'h1, h2, h3, h4, h5, h6',
            'h1' => 'h1',
            'h2' => 'h2',
            'h3' => 'h3',
            'h4' => 'h4',
            'h5' => 'h5',
            'h6' => 'h6',
            'paragraph' => 'p',
            'link' => 'a',
            'button' => '.df-button'
        ];
        
        return $selectors[$element] ?? $element;
    }
    
    private function render_layout($layout) {
        if (!$layout) return '';
        
        $css = [];
        
        foreach ($layout as $section => $styles) {
            $selector = $this->get_layout_selector($section);
            
            $css[] = sprintf(
                '%s {
                    max-width: %s;
                    padding: %s;
                    margin: %s;
                }',
                $selector,
                esc_attr($styles['max-width']),
                esc_attr($styles['padding']),
                esc_attr($styles['margin'])
            );
        }
        
        return implode("\n", $css);
    }
    
    private function get_layout_selector($section) {
        $selectors = [
            'container' => '.df-container',
            'header' => '.df-header',
            'footer' => '.df-footer',
            'main' => '.df-main',
            'sidebar' => '.df-sidebar'
        ];
        
        return $selectors[$section] ?? $section;
    }
    
    private function render_custom_elements($elements) {
        if (!$elements) return '';
        
        $css = [];
        
        foreach ($elements as $element) {
            if (isset($element['selector']) && isset($element['styles'])) {
                $css[] = sprintf(
                    '%s {
                        %s
                    }',
                    esc_attr($element['selector']),
                    $this->render_styles_array($element['styles'])
                );
            }
        }
        
        return implode("\n", $css);
    }
    
    private function render_styles_array($styles) {
        $css = [];
        
        foreach ($styles as $property => $value) {
            $css[] = sprintf(
                '%s: %s;',
                esc_attr($property),
                esc_attr($value)
            );
        }
        
        return implode("\n    ", $css);
    }
    
    public function render_scripts($site_id) {
        $customization = $this->customization->get_customization($site_id);
        if (!$customization || !isset($customization['scripts'])) {
            return '';
        }
        
        $js = [];
        
        foreach ($customization['scripts'] as $script) {
            if (isset($script['content'])) {
                $js[] = $script['content'];
            }
        }
        
        return implode("\n", $js);
    }
    
    public function render_preview($customization) {
        // Gerar preview em tempo real das customizações
        $html = '<div class="df-preview">';
        
        // Header
        $html .= $this->render_preview_header($customization);
        
        // Hero
        $html .= $this->render_preview_hero($customization);
        
        // Content
        $html .= $this->render_preview_content($customization);
        
        // Footer
        $html .= $this->render_preview_footer($customization);
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function render_preview_header($customization) {
        return sprintf(
            '<header class="df-preview-header">
                <div class="df-container">
                    <div class="df-logo">
                        <img src="%s" alt="Logo">
                    </div>
                    <nav class="df-nav">
                        <a href="#">Home</a>
                        <a href="#">Produtos</a>
                        <a href="#">Sobre</a>
                        <a href="#">Contato</a>
                    </nav>
                </div>
            </header>',
            esc_url($customization['logo'] ?? '')
        );
    }
    
    private function render_preview_hero($customization) {
        return sprintf(
            '<section class="df-preview-hero">
                <div class="df-container">
                    <h1>%s</h1>
                    <p>%s</p>
                    <button class="df-button">%s</button>
                </div>
            </section>',
            esc_html($customization['hero']['title'] ?? 'Título Principal'),
            esc_html($customization['hero']['description'] ?? 'Descrição do site'),
            esc_html($customization['hero']['button_text'] ?? 'Botão')
        );
    }
    
    private function render_preview_content($customization) {
        return '<section class="df-preview-content">
            <div class="df-container">
                <h2>Seção de Conteúdo</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                <div class="df-grid">
                    <div class="df-card">
                        <h3>Card 1</h3>
                        <p>Descrição do card 1</p>
                    </div>
                    <div class="df-card">
                        <h3>Card 2</h3>
                        <p>Descrição do card 2</p>
                    </div>
                    <div class="df-card">
                        <h3>Card 3</h3>
                        <p>Descrição do card 3</p>
                    </div>
                </div>
            </div>
        </section>';
    }
    
    private function render_preview_footer($customization) {
        return sprintf(
            '<footer class="df-preview-footer">
                <div class="df-container">
                    <div class="df-footer-content">
                        <div class="df-footer-logo">
                            <img src="%s" alt="Logo">
                        </div>
                        <div class="df-footer-links">
                            <h4>Links</h4>
                            <a href="#">Link 1</a>
                            <a href="#">Link 2</a>
                            <a href="#">Link 3</a>
                        </div>
                        <div class="df-footer-contact">
                            <h4>Contato</h4>
                            <p>%s</p>
                            <p>%s</p>
                        </div>
                    </div>
                    <div class="df-footer-bottom">
                        <p>&copy; 2024 %s. Todos os direitos reservados.</p>
                    </div>
                </div>
            </footer>',
            esc_url($customization['logo'] ?? ''),
            esc_html($customization['contact']['email'] ?? 'contato@exemplo.com'),
            esc_html($customization['contact']['phone'] ?? '(00) 0000-0000'),
            esc_html($customization['site_name'] ?? 'Nome do Site')
        );
    }
}