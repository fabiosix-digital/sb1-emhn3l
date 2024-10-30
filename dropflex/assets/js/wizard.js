/**
 * DropFlex Wizard
 */

(function($) {
    'use strict';
    
    const DropFlexWizard = {
        currentStep: 1,
        totalSteps: 6,
        data: {},
        
        init: function() {
            this.bindEvents();
            this.showCurrentStep();
            this.initMediaUploader();
        },
        
        bindEvents: function() {
            $('.dropflex-next-step').on('click', this.nextStep.bind(this));
            $('.dropflex-prev-step').on('click', this.prevStep.bind(this));
            
            // Seleção de tipo de negócio
            $('.dropflex-business-type').on('click', function() {
                $('.dropflex-business-type').removeClass('selected');
                $(this).addClass('selected');
                $('#step-business-type .dropflex-next-step').prop('disabled', false);
                
                this.data.businessType = $(this).data('type');
            }.bind(this));
            
            // Seleção de template
            $('.dropflex-template-card').on('click', function() {
                $('.dropflex-template-card').removeClass('selected');
                $(this).addClass('selected');
                $('#step-template .dropflex-next-step').prop('disabled', false);
                
                this.data.templateId = $(this).data('template-id');
            }.bind(this));
            
            // Preview de template
            $('.template-preview-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const templateId = $(this).closest('.dropflex-template-card').data('template-id');
                this.previewTemplate(templateId);
            }.bind(this));
            
            // Seleção de header
            $('.select-header').on('click', function() {
                const $card = $(this).closest('.dropflex-header-card');
                $('.dropflex-header-card').removeClass('selected');
                $card.addClass('selected');
                $('#step-header .dropflex-next-step').prop('disabled', false);
                
                this.data.headerId = $card.data('header-id');
            }.bind(this));
            
            // Seleção de footer
            $('.select-footer').on('click', function() {
                const $card = $(this).closest('.dropflex-footer-card');
                $('.dropflex-footer-card').removeClass('selected');
                $card.addClass('selected');
                $('#step-footer .dropflex-next-step').prop('disabled', false);
                
                this.data.footerId = $card.data('footer-id');
            }.bind(this));
            
            // Form de conteúdo
            $('#content-form').on('submit', function(e) {
                e.preventDefault();
                this.saveContent();
            }.bind(this));
        },
        
        showCurrentStep: function() {
            $('.dropflex-wizard-step').hide();
            $(`#step-${this.getCurrentStepName()}`).show();
            this.updateProgress();
        },
        
        getCurrentStepName: function() {
            switch(this.currentStep) {
                case 1: return 'business-type';
                case 2: return 'template';
                case 3: return 'header';
                case 4: return 'colors';
                case 5: return 'content';
                case 6: return 'settings';
                default: return '';
            }
        },
        
        nextStep: function() {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showCurrentStep();
                this.saveProgress();
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showCurrentStep();
            }
        },
        
        updateProgress: function() {
            const progress = (this.currentStep / this.totalSteps) * 100;
            $('.dropflex-wizard-progress-bar').css('width', `${progress}%`);
            $('.dropflex-wizard-step-count').text(`${this.currentStep}/${this.totalSteps}`);
        },
        
        initMediaUploader: function() {
            if (typeof wp === 'undefined' || !wp.media) {
                return;
            }
            
            $('#upload-logo').on('click', function(e) {
                e.preventDefault();
                
                const uploader = wp.media({
                    title: dropflexAdmin.i18n.selectLogo,
                    button: {
                        text: dropflexAdmin.i18n.useLogo
                    },
                    multiple: false
                });
                
                uploader.on('select', function() {
                    const attachment = uploader.state().get('selection').first().toJSON();
                    $('#site_logo').val(attachment.id);
                    $('#logo-preview').html(`<img src="${attachment.url}" alt="Logo">`);
                });
                
                uploader.open();
            });
        },
        
        previewTemplate: function(templateId) {
            const $modal = $('#template-preview-modal');
            const $iframe = $modal.find('iframe');
            
            $iframe.attr('src', `${dropflexAdmin.previewUrl}?template=${templateId}`);
            $modal.show();
        },
        
        saveProgress: function() {
            const data = {
                action: 'dropflex_save_wizard_progress',
                nonce: dropflexAdmin.nonce,
                step: this.currentStep,
                data: this.data
            };
            
            $.ajax({
                url: dropflexAdmin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (!response.success) {
                        console.error('Erro ao salvar progresso:', response.data);
                    }
                }
            });
        },
        
        saveContent: function() {
            const formData = new FormData($('#content-form')[0]);
            formData.append('action', 'dropflex_save_content');
            formData.append('nonce', dropflexAdmin.nonce);
            
            $.ajax({
                url: dropflexAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.nextStep();
                    } else {
                        alert(response.data || 'Erro ao salvar conteúdo');
                    }
                }.bind(this)
            });
        }
    };
    
    $(document).ready(function() {
        if ($('.dropflex-wizard').length) {
            DropFlexWizard.init();
        }
    });
    
    // Fechar modais
    $('.dropflex-modal-close').on('click', function() {
        $(this).closest('.dropflex-modal').hide();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('dropflex-modal')) {
            $('.dropflex-modal').hide();
        }
    });
    
})(jQuery);