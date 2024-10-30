/**
 * DropFlex Wizard Frontend
 */

(function($) {
    'use strict';
    
    const DropFlexWizard = {
        currentStep: 1,
        totalSteps: 6,
        data: {},
        
        init: function() {
            this.initSteps();
            this.initForms();
            this.initButtons();
            this.updateProgress();
        },
        
        initSteps: function() {
            $('.wizard-step-item').on('click', function() {
                const step = $(this).data('step');
                if ($(this).hasClass('completed')) {
                    this.goToStep(step);
                }
            }.bind(this));
        },
        
        initForms: function() {
            // Store Form
            $('#store-form').on('submit', function(e) {
                e.preventDefault();
                this.saveStoreData();
            }.bind(this));
            
            // Domain availability check
            $('#store-domain').on('input', this.debounce(function(e) {
                this.checkDomainAvailability(e.target.value);
            }.bind(this), 500));
            
            // Template selection
            $('.template-card').on('click', function() {
                $('.template-card').removeClass('selected');
                $(this).addClass('selected');
                this.data.templateId = $(this).data('template-id');
                this.enableNextButton();
            }.bind(this));
            
            // Color picker initialization
            $('.color-picker').each(function() {
                $(this).wpColorPicker({
                    change: function(event, ui) {
                        this.updateColorPreview();
                    }.bind(this)
                });
            }.bind(this));
        },
        
        initButtons: function() {
            $('.wizard-btn-next').on('click', function() {
                this.nextStep();
            }.bind(this));
            
            $('.wizard-btn-prev').on('click', function() {
                this.prevStep();
            }.bind(this));
            
            $('.wizard-btn-finish').on('click', function() {
                this.finishWizard();
            }.bind(this));
        },
        
        saveStoreData: function() {
            const formData = new FormData($('#store-form')[0]);
            formData.append('action', 'dropflex_save_store');
            formData.append('nonce', dropflexWizard.nonce);
            
            $.ajax({
                url: dropflexWizard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.data.store = response.data;
                        this.nextStep();
                    } else {
                        this.showError(response.data);
                    }
                }.bind(this)
            });
        },
        
        checkDomainAvailability: function(domain) {
            if (!domain) return;
            
            $.ajax({
                url: dropflexWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dropflex_check_domain',
                    nonce: dropflexWizard.nonce,
                    domain: domain
                },
                success: function(response) {
                    const $input = $('#store-domain');
                    const $hint = $('#domain-hint');
                    
                    if (response.available) {
                        $input.removeClass('error').addClass('success');
                        $hint.removeClass('error').addClass('success')
                            .text('Domínio disponível!');
                        this.enableNextButton();
                    } else {
                        $input.removeClass('success').addClass('error');
                        $hint.removeClass('success').addClass('error')
                            .text('Este domínio já está em uso.');
                        this.disableNextButton();
                    }
                }.bind(this)
            });
        },
        
        updateColorPreview: function() {
            const colors = {};
            $('.color-picker').each(function() {
                const $input = $(this);
                colors[$input.attr('name')] = $input.val();
            });
            
            const $preview = $('.color-preview');
            $preview.css({
                '--primary-color': colors['primary'],
                '--secondary-color': colors['secondary'],
                '--accent-color': colors['accent'],
                '--background-color': colors['background'],
                '--text-color': colors['text']
            });
        },
        
        nextStep: function() {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateProgress();
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateProgress();
            }
        },
        
        goToStep: function(step) {
            if (step <= this.currentStep) {
                this.currentStep = step;
                this.showStep(step);
                this.updateProgress();
            }
        },
        
        showStep: function(step) {
            $('.step-content').hide();
            $(`#step-${step}`).show();
            
            $('.wizard-step-item').removeClass('active');
            $(`.wizard-step-item[data-step="${step}"]`).addClass('active');
            
            this.updateButtons();
        },
        
        updateProgress: function() {
            const progress = (this.currentStep / this.totalSteps) * 100;
            $('.progress-fill').css('width', `${progress}%`);
        },
        
        updateButtons: function() {
            const $prevBtn = $('.wizard-btn-prev');
            const $nextBtn = $('.wizard-btn-next');
            const $finishBtn = $('.wizard-btn-finish');
            
            // Previous button
            if (this.currentStep === 1) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }
            
            // Next/Finish buttons
            if (this.currentStep === this.totalSteps) {
                $nextBtn.hide();
                $finishBtn.show();
            } else {
                $nextBtn.show();
                $finishBtn.hide();
            }
        },
        
        enableNextButton: function() {
            $('.wizard-btn-next').prop('disabled', false);
        },
        
        disableNextButton: function() {
            $('.wizard-btn-next').prop('disabled', true);
        },
        
        finishWizard: function() {
            $.ajax({
                url: dropflexWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dropflex_finish_wizard',
                    nonce: dropflexWizard.nonce,
                    data: this.data
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        this.showError(response.data);
                    }
                }.bind(this)
            });
        },
        
        showError: function(message) {
            // Implementar exibição de erro
            console.error(message);
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    $(document).ready(function() {
        DropFlexWizard.init();
    });
    
})(jQuery);