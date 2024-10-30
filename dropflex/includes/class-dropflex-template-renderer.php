<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Template_Renderer {
    private $loader;
    private $customization;
    
    public function __construct() {
        $this->loader = new DropFlex_Template_Loader();
        $this->customization = new DropFlex_Customization_Manager();
    }
    
    public function render_site($site_id) {
        $site = $this->get_site_data($site_id);
        if (!$site) {
            return false;
        }
        
        // Renderizar header
        $header = $this->render_header($site);
        
        // Renderizar conteúdo
        $content = $this->render_content($site);
        
        // Renderizar footer
        $footer = $this->render_footer($site);
        
        return $header . $content . $footer;
    }
    
    private function get_site_data($site_id) {
        global $wpdb;
        
        $site = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dropflex_sites WHERE id = %d",
                $site_id
            )
        );
        
        if (!$site) {
            return false;
        }
        
        // Carregar customizações
        $site->customization = $this->customization->get_customization($site_id);
        
        return $site;
    }
    
    private function render_header($site) {
        $template = $this->loader->load_template('headers', $site->header_id);
        if (!$template) {
            return '';
        }
        
        $data = [
            'site_name' => $site->name,
            'logo_url' => $site->customization['logo'] ?? '',
            'primary_menu' => $this->get_menu_html('primary'),
            'phone' => $site->customization['contact']['phone'] ?? '',
            'email' => $site->customization['contact']['email'] ?? ''
        ];
        
        return $this->loader->render_template($template, $data);
    }
    
    private function render_content($site) {
        $template_type = $this->get_current_template_type();
        $template_id = $this->get_template_id($site, $template_type);
        
        $template = $this->loader->load_template($template_type, $template_id);
        if (!$template) {
            return '';
        }
        
        $data = $this->get_template_data($site, $template_type);
        
        return $this->loader->render_template($template, $data);
    }
    
    private function render_footer($site) {
        $template = $this->loader->load_template('footers', $site->footer_id);
        if (!$template) {
            return '';
        }
        
        $data = [
            'site_name' => $site->name,
            'logo_url' => $site->customization['logo'] ?? '',
            'footer_menu_1' => $this->get_menu_html('footer-1'),
            'footer_menu_2' => $this->get_menu_html('footer-2'),
            'address' => $site->customization['contact']['address'] ?? '',
            'phone' => $site->customization['contact']['phone'] ?? '',
            'email' => $site->customization['contact']['email'] ?? '',
            'social_links' => $this->get_social_links_html($site->customization['social'] ?? []),
            'current_year' => date('Y')
        ];
        
        return $this->loader->render_template($template, $data);
    }
    
    private function get_current_template_type() {
        if (is_front_page()) {
            return 'home';
        } elseif (is_product()) {
            return 'product';
        } elseif (is_product_category()) {
            return 'products';
        } elseif (is_cart()) {
            return 'cart';
        } elseif (is_checkout()) {
            return 'checkout';
        } elseif (is_page('about')) {
            return 'about';
        } elseif (is_page('contact')) {
            return 'contact';
        }
        
        return 'page';
    }
    
    private function get_template_id($site, $type) {
        $template_map = [
            'home' => $site->home_template_id,
            'product' => $site->product_template_id,
            'products' => $site->products_template_id,
            'cart' => $site->cart_template_id,
            'checkout' => $site->checkout_template_id,
            'about' => $site->about_template_id,
            'contact' => $site->contact_template_id
        ];
        
        return $template_map[$type] ?? null;
    }
    
    private function get_template_data($site, $type) {
        $data = [
            'site_name' => $site->name,
            'site_description' => $site->description
        ];
        
        switch ($type) {
            case 'home':
                $data = array_merge($data, $this->get_home_data($site));
                break;
                
            case 'product':
                $data = array_merge($data, $this->get_product_data());
                break;
                
            case 'products':
                $data = array_merge($data, $this->get_products_data());
                break;
                
            case 'cart':
                $data = array_merge($data, $this->get_cart_data());
                break;
                
            case 'checkout':
                $data = array_merge($data, $this->get_checkout_data());
                break;
        }
        
        return $data;
    }
    
    private function get_home_data($site) {
        return [
            'hero_slides' => $this->get_hero_slides_html($site->customization['home']['slides'] ?? []),
            'featured_categories' => $this->get_featured_categories_html(),
            'featured_products' => $this->get_featured_products_html(),
            'promo_title' => $site->customization['home']['promo']['title'] ?? '',
            'promo_description' => $site->customization['home']['promo']['description'] ?? '',
            'promo_button_text' => $site->customization['home']['promo']['button_text'] ?? '',
            'promo_link' => $site->customization['home']['promo']['link'] ?? '',
            'newsletter_title' => $site->customization['home']['newsletter']['title'] ?? '',
            'newsletter_description' => $site->customization['home']['newsletter']['description'] ?? ''
        ];
    }
    
    private function get_menu_html($location) {
        // Implementar geração do menu
        return '';
    }
    
    private function get_social_links_html($social) {
        $html = '';
        
        foreach ($social as $network => $url) {
            $html .= sprintf(
                '<a href="%s" target="_blank" class="social-link social-%s">
                    <i class="fab fa-{$network}"></i>
                </a>',
                esc_url($url),
                esc_attr($network)
            );
        }
        
        return $html;
    }
    
    private function get_hero_slides_html($slides) {
        $html = '';
        
        foreach ($slides as $slide) {
            $html .= sprintf(
                '<div class="df-slide">
                    <img src="%s" alt="%s">
                    <div class="slide-content">
                        <h2>%s</h2>
                        <p>%s</p>
                        <a href="%s" class="df-button">%s</a>
                    </div>
                </div>',
                esc_url($slide['image']),
                esc_attr($slide['title']),
                esc_html($slide['title']),
                esc_html($slide['description']),
                esc_url($slide['link']),
                esc_html($slide['button_text'])
            );
        }
        
        return $html;
    }
    
    private function get_featured_categories_html() {
        // Implementar listagem de categorias em destaque
        return '';
    }
    
    private function get_featured_products_html() {
        // Implementar listagem de produtos em destaque
        return '';
    }
    
    private function get_product_data() {
        // Implementar dados do produto atual
        return [];
    }
    
    private function get_products_data() {
        // Implementar dados da listagem de produtos
        return [];
    }
    
    private function get_cart_data() {
        // Implementar dados do carrinho
        return [];
    }
    
    private function get_checkout_data() {
        // Implementar dados do checkout
        return [];
    }
}