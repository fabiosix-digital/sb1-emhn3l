/**
 * DropFlex Wizard - Customização
 */

(function($) {
    'use strict';
    
    const DropFlexCustomization = {
        init: function() {
            this.initColorPickers();
            this.initSchemes();
            this.initPreview();
            this.bindEvents();
        },
        
        initColorPickers: function() {
            $('.dropflex-color-picker').wpColorPicker({
                change: this.updatePreview.bind(this),
                clear: this.resetColor.bind(this)
            });
        },
        
        initSchemes: function() {
            $('.apply-scheme').on('click', function(e) {
                e.preventDefault();
                const $scheme = $(this).closest('.dropflex-scheme-card');
                const colors = {};
                
                $scheme.find('.color-swatch').each(function() {
                    const $swatch = $(this);
                    colors[$swatch.data('name')] = $swatch.data('color');
                });
                
                this.applyColorScheme(colors);
            }.bind(this));
        },
        
        initPreview: function() {
            this.updatePreview();
        },
        
        bindEvents: function() {
            $('.dropflex-next-step').on('click', this.saveCustomization.bind(this));
        },
        
        applyColorScheme: function(colors) {
            Object.keys(colors).forEach(name => {
                const $input = $(`input[name="colors[${name}]"]`);
                if ($input.length) {
                    $input.wpColorPicker('color', colors[name]);
                }
            });
            
            this.updatePreview();
        },
        
        updatePreview: function() {
            const colors = this.getColors();
            const $preview = $('.preview-container');
            
            // Atualizar CSS do preview
            const previewCSS = `
                .preview-header {
                    background-color: ${colors.background};
                    border-bottom: 1px solid ${colors.text}20;
                }
                
                .preview-nav a {
                    color: ${colors.text};
                }
                
                .preview-nav a:hover {
                    color: ${colors.primary};
                }
                
                .preview-hero {
                    background-color: ${colors.primary};
                    color: ${colors.background};
                }
                
                .preview-button {
                    background-color: ${colors.accent};
                    color: ${colors.background};
                }
                
                .preview-content {
                    color: ${colors.text};
                }
            `;
            
            // Atualizar HTML do preview
            $preview.html(`
                <style>${previewCSS}</style>
                <div class="preview-header">
                    <nav class="preview-nav">
                        <a href="#">Home</a>
                        <a href="#">Sobre</a>
                        <a href="#">Contato</a>
                    </nav>
                </div>
                
                <div class="preview-hero">
                    <h1>Título Principal</h1>
                    <p>Subtítulo ou descrição do site</p>
                    <button class="preview-button">Botão de Ação</button>
                </div>
                
                <div class="preview-content">
                    <h2>Seção de Conteúdo</h2>
                    <p>Exemplo de texto para visualizar as cores.</p>
                </div>
            `);
        },
        
        getColors: function() {
            const colors = {};
            $('.dropflex-color-picker').each(function() {
                const name = $(this).attr('name').match(/\[(.*?)\]/)[1];
                colors[name] = $(this).val();
            });
            return colors;
        },
        
        resetColor: function($input) {
            const defaultColor = $input.data('default-color');
            $input.wpColorPicker('color', defaultColor);
            this.updatePreview();
        },
        
        saveCustomization: function(e) {
            e.preventDefault();
            
            const data = {
                action: 'dropflex_save_customization',
                nonce: dropflexAdmin.nonce,
                site_id: $('#site_id').val(),
                customization: {
                    colors: this.getColors()
                }
            };
            
            $.ajax({
                url: dropflexAdmin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        DropFlexWizard.nextStep();
                    } else {
                        alert(response.data || 'Erro ao salvar customização');
                    }
                },
                error: function() {
                    alert('Erro ao salvar customização');
                }
            });
        }
    };
    
    $(document).ready(function() {
        if ($('#step-colors').length) {
            DropFlexCustomization.init();
        }
    });
    
})(jQuery);