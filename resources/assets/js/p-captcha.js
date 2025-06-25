/**
 * P-CAPTCHA Frontend JavaScript
 * Handles all CAPTCHA interactions and validations
 */
class PCaptchaWidget {
    constructor(containerId) {
        this.containerId = containerId;
        this.containerEl = document.getElementById(containerId);
        this.verified = false;
        this.solution = {};
        this.currentChallenge = null;

        // Debug logging (only when APP_DEBUG is enabled)
        if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
            console.log('P-CAPTCHA: Widget constructor called', {
                container_id: containerId,
                container_exists: !!this.containerEl,
                debug_enabled: window.pCaptchaDebug?.enabled,
                force_visual_captcha: window.pCaptchaConfig?.force_visual_captcha || false
            });
        }

        if (!this.containerEl) {
            console.error(`P-CAPTCHA: Container with ID '${containerId}' not found`);
            return;
        }

        this.init();
    }

    async init() {
        // Debug logging (only when APP_DEBUG is enabled)
        if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
            console.log('P-CAPTCHA: Init method called', {
                container_id: this.containerId,
                container_exists: !!this.containerEl,
                auto_load: this.containerEl?.getAttribute('data-auto-load') === 'true'
            });
        }

        this.setupElements();
        await this.loadChallenge();

        // Debug logging (only when APP_DEBUG is enabled)
        if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
            console.log('P-CAPTCHA: Widget initialized', {
                container_id: this.containerId,
                force_visual_captcha: window.pCaptchaConfig?.force_visual_captcha || false,
                auto_show_after_attempts: window.pCaptchaConfig?.auto_show_after_attempts || 3
            });
        }
    }

    setupElements() {
        this.instructionsEl = this.containerEl.querySelector(`#${this.containerId}-instructions`);
        this.challengeEl = this.containerEl.querySelector(`#${this.containerId}-challenge`);
        this.statusEl = this.containerEl.querySelector(`#${this.containerId}-status`);
        this.validateBtn = this.containerEl.querySelector(`#${this.containerId}-validate`);
        this.challengeIdInput = this.containerEl.querySelector(`#${this.containerId}-challenge-id`);
        this.solutionInput = this.containerEl.querySelector(`#${this.containerId}-solution`);
    }

    async loadChallenge() {
        try {
            console.log('P-CAPTCHA: Starting to load challenge...');
            
            // Debug logging (only when APP_DEBUG is enabled)
            if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                console.log('P-CAPTCHA: Loading challenge...');
            }

            const response = await fetch('/p-captcha/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            console.log('P-CAPTCHA: Response received, status:', response.status);

            const data = await response.json();

            console.log('P-CAPTCHA: Response data:', data);

            // Debug logging (only when APP_DEBUG is enabled)
            if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                console.log('P-CAPTCHA: Challenge response', data);
            }

            if (data.success && data.challenge) {
                this.currentChallenge = data.challenge;
                this.renderChallenge();
            } else {
                throw new Error(data.message || this.translate('failed_to_load_challenge'));
            }
        } catch (error) {
            console.error('P-CAPTCHA: Failed to load challenge', error);
            this.showError(this.translate('failed_to_load_challenge'));
        }
    }

    renderChallenge() {
        console.log('P-CAPTCHA: renderChallenge called, currentChallenge:', this.currentChallenge);
        
        if (!this.currentChallenge) {
            console.log('P-CAPTCHA: No challenge to render');
            // Debug logging (only when APP_DEBUG is enabled)
            if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                console.log('P-CAPTCHA: No challenge to render');
            }
            return;
        }

        console.log('P-CAPTCHA: Rendering challenge type:', this.currentChallenge.type);

        // Debug logging (only when APP_DEBUG is enabled)
        if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
            console.log('P-CAPTCHA: Rendering challenge', {
                challenge_type: this.currentChallenge.type,
                challenge_id: this.currentChallenge.id
            });
        }

        // Set challenge ID in hidden field
        this.challengeIdInput.value = this.currentChallenge.id;

        // Update instructions
        if (this.instructionsEl) {
            this.instructionsEl.textContent = this.currentChallenge.instructions || this.translate('complete_challenge_below');
        }

        // Render based on challenge type
        switch (this.currentChallenge.type) {
            case 'beam_alignment':
                console.log('P-CAPTCHA: Rendering beam alignment challenge');
                this.renderBeamAlignment();
                break;
            case 'sequence_complete':
                console.log('P-CAPTCHA: Rendering sequence complete challenge');
                this.renderSequenceComplete();
                break;
            default:
                console.error('P-CAPTCHA: Unknown challenge type:', this.currentChallenge.type);
                this.showError(this.translate('unknown_challenge_type', { type: this.currentChallenge.type }));
        }
    }

    renderBeamAlignment() {
        const data = this.currentChallenge.challenge_data;

        this.challengeEl.innerHTML = `
            <div class="beam-canvas" id="${this.containerId}-beam-canvas"
                 style="width: ${data.canvas_width}px; height: ${data.canvas_height}px;">
                <div class="beam-source" id="${this.containerId}-beam-source"
                     style="left: ${data.source.x}px; top: ${data.source.y}px;"></div>
                <div class="beam-target" id="${this.containerId}-beam-target"
                     style="left: ${data.target.x}px; top: ${data.target.y}px;"></div>
            </div>
        `;

        this.setupBeamAlignment();
    }

    setupBeamAlignment() {
        const canvas = this.containerEl.querySelector(`#${this.containerId}-beam-canvas`);
        const source = this.containerEl.querySelector(`#${this.containerId}-beam-source`);
        const target = this.containerEl.querySelector(`#${this.containerId}-beam-target`);

        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        // Mouse events
        source.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(source.style.left);
            initialTop = parseInt(source.style.top);
            source.style.cursor = 'grabbing';
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            const newLeft = Math.max(0, Math.min(initialLeft + deltaX, canvas.offsetWidth - 30));
            const newTop = Math.max(0, Math.min(initialTop + deltaY, canvas.offsetHeight - 30));

            source.style.left = newLeft + 'px';
            source.style.top = newTop + 'px';

            this.updateBeamLine();
            this.checkBeamAlignment();
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                source.style.cursor = 'grab';
            }
        });

        // Touch events for mobile
        source.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            source.dispatchEvent(mouseEvent);
            e.preventDefault();
        });

        document.addEventListener('touchmove', (e) => {
            if (isDragging) {
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                document.dispatchEvent(mouseEvent);
                e.preventDefault();
            }
        });

        document.addEventListener('touchend', () => {
            if (isDragging) {
                const mouseEvent = new MouseEvent('mouseup', {});
                document.dispatchEvent(mouseEvent);
            }
        });

        this.updateBeamLine();
    }

    updateBeamLine() {
        const source = this.containerEl.querySelector(`#${this.containerId}-beam-source`);
        const target = this.containerEl.querySelector(`#${this.containerId}-beam-target`);

        if (!source || !target) return;

        const sourceX = parseInt(source.style.left) + 15; // Center of source
        const sourceY = parseInt(source.style.top) + 15;
        const targetX = parseInt(target.style.left) + 15; // Center of target
        const targetY = parseInt(target.style.top) + 15;

        const distance = Math.sqrt(Math.pow(targetX - sourceX, 2) + Math.pow(targetY - sourceY, 2));
        const angle = Math.atan2(targetY - sourceY, targetX - sourceX) * 180 / Math.PI;

        let beamLine = this.containerEl.querySelector(`#${this.containerId}-beam-line`);
        if (!beamLine) {
            beamLine = document.createElement('div');
            beamLine.id = `${this.containerId}-beam-line`;
            beamLine.className = 'beam-line';
            source.parentNode.appendChild(beamLine);
        }

        beamLine.style.left = sourceX + 'px';
        beamLine.style.top = sourceY + 'px';
        beamLine.style.width = distance + 'px';
        beamLine.style.transform = `rotate(${angle}deg)`;
    }

    checkBeamAlignment() {
        const source = this.containerEl.querySelector(`#${this.containerId}-beam-source`);
        const target = this.containerEl.querySelector(`#${this.containerId}-beam-target`);
        const data = this.currentChallenge.challenge_data;

        const sourceX = parseInt(source.style.left);
        const sourceY = parseInt(source.style.top);
        const offsetX = sourceX - data.source.x;
        const offsetY = sourceY - data.source.y;

        this.solution = { offset_x: offsetX, offset_y: offsetY };

        // Check if close enough to target
        const targetX = data.target.x;
        const targetY = data.target.y;
        const currentTargetX = data.source.x + offsetX;
        const currentTargetY = data.source.y + offsetY;

        const distance = Math.sqrt(Math.pow(targetX - currentTargetX, 2) + Math.pow(targetY - currentTargetY, 2));

        if (distance <= data.tolerance) {
            this.validateBtn.disabled = false;
            this.showStatus(this.translate('beam_aligned'), 'success');
        } else {
            this.validateBtn.disabled = true;
            this.hideStatus();
        }
    }

    renderSequenceComplete() {
        const data = this.currentChallenge.challenge_data;

        let sequenceHtml = '<div class="sequence-display">';
        data.sequence.forEach(number => {
            sequenceHtml += `<span class="sequence-number">${number}</span>`;
        });
        sequenceHtml += '<span class="sequence-number">?</span>';
        sequenceHtml += '</div>';

        sequenceHtml += '<div class="sequence-choices">';
        data.choices.forEach(choice => {
            sequenceHtml += `<div class="sequence-choice" data-choice="${choice}" onclick="PCaptcha.selectSequenceChoice('${this.containerId}', ${choice})">${choice}</div>`;
        });
        sequenceHtml += '</div>';

        this.challengeEl.innerHTML = sequenceHtml;
    }

    selectSequenceChoice(choice) {
        // Remove previous selection
        this.containerEl.querySelectorAll('.sequence-choice').forEach(el => {
            el.classList.remove('selected');
        });

        // Select new choice
        const choiceEl = this.containerEl.querySelector(`[data-choice="${choice}"]`);
        if (choiceEl) {
            choiceEl.classList.add('selected');
            this.solution = { answer: parseInt(choice) };
            this.validateBtn.disabled = false;
        }
    }

    async validateSolution() {
        if (!this.currentChallenge || Object.keys(this.solution).length === 0) {
            this.showError(this.translate('please_complete_challenge_first'));
            return;
        }

        try {
            this.validateBtn.disabled = true;
            this.showStatus(this.translate('validating'), 'info');

            // Debug logging (only when APP_DEBUG is enabled)
            if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                console.log('P-CAPTCHA: Sending validation request', {
                    challenge_id: this.currentChallenge.id,
                    solution: this.solution,
                    challenge_type: this.currentChallenge.type
                });
            }

            const response = await fetch('/p-captcha/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({
                    challenge_id: this.currentChallenge.id,
                    solution: this.solution
                })
            });

            const data = await response.json();

            // Debug logging (only when APP_DEBUG is enabled)
            if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                console.log('P-CAPTCHA: Validation response', data);
            }

            if (data.success && data.valid) {
                this.verified = true;
                this.solutionInput.value = JSON.stringify(this.solution);
                this.showStatus(this.translate('captcha_verified_successfully'), 'success');

                // Debug logging (only when APP_DEBUG is enabled)
                if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
                    console.log('P-CAPTCHA: Solution stored in hidden field', {
                        challenge_id: this.challengeIdInput.value,
                        solution: this.solutionInput.value
                    });
                }

                // Disable further interaction
                this.challengeEl.style.pointerEvents = 'none';
                this.challengeEl.style.opacity = '0.7';

                // Trigger form validation update
                this.triggerFormUpdate();

            } else {
                this.showError(data.message || this.translate('invalid_solution_try_again'));
                this.validateBtn.disabled = false;
            }

        } catch (error) {
            console.error('P-CAPTCHA: Validation failed', error);
            this.showError(this.translate('network_error_try_again'));
            this.validateBtn.disabled = false;
        }
    }

    async refresh() {
        this.verified = false;
        this.solution = {};
        this.challengeIdInput.value = '';
        this.solutionInput.value = '';
        this.hideStatus();
        this.validateBtn.disabled = true;

        // Re-enable interaction
        this.challengeEl.style.pointerEvents = 'auto';
        this.challengeEl.style.opacity = '1';

        await this.loadChallenge();
    }

    showLoading() {
        this.challengeEl.innerHTML = `
            <div class="p-captcha-loading">
                <div class="p-captcha-spinner"></div>
                <div>${this.translate('loading_challenge')}</div>
            </div>
        `;
    }

    showStatus(message, type = 'info') {
        this.statusEl.textContent = message;
        this.statusEl.className = `p-captcha-status ${type} show`;
    }

    showError(message) {
        this.showStatus(message, 'error');
    }

    hideStatus() {
        this.statusEl.classList.remove('show');
    }

    triggerFormUpdate() {
        // Trigger custom event for form libraries
        const event = new CustomEvent('p-captcha-verified', {
            detail: { containerId: this.containerId, verified: true }
        });
        document.dispatchEvent(event);

        // Trigger change event on hidden inputs
        this.challengeIdInput.dispatchEvent(new Event('change'));
        this.solutionInput.dispatchEvent(new Event('change'));
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    isVerified() {
        return this.verified;
    }

    /**
     * Translate text using Laravel's translation system
     */
    translate(key, parameters = {}) {
        // Get translations from window object (set by Laravel)
        const translations = window.pCaptchaTranslations || {};
        let text = translations[key] || key;
        
        // Replace parameters
        Object.keys(parameters).forEach(param => {
            text = text.replace(`:${param}`, parameters[param]);
        });
        
        return text;
    }

    /**
     * Render the CAPTCHA widget
     */
    render() {
        // Debug logging (only when APP_DEBUG is enabled)
        if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
            console.log('P-CAPTCHA: Rendering widget', {
                container_id: this.containerId,
                widget_visible: true
            });
        }

        this.containerEl.innerHTML = `
            <div class="p-captcha-widget" id="${this.containerId}-widget">
                <div class="p-captcha-header">
                    <h3>${this.translate('human_verification')}</h3>
                    <p class="p-captcha-instructions">${this.translate('complete_challenge_below')}</p>
                </div>
                <div class="p-captcha-content">
                    <div class="p-captcha-challenge" id="${this.containerId}-challenge">
                        <div class="p-captcha-loading">${this.translate('loading_challenge')}</div>
                    </div>
                    <div class="p-captcha-controls">
                        <button type="button" class="p-captcha-validate-btn" id="${this.containerId}-validate-btn" disabled>
                            ${this.translate('validate')}
                        </button>
                        <button type="button" class="p-captcha-refresh-btn" id="${this.containerId}-refresh-btn">
                            ↻ ${this.translate('new_challenge')}
                        </button>
                    </div>
                </div>
                <div class="p-captcha-status" id="${this.containerId}-status"></div>
                <input type="hidden" name="p_captcha_id" id="${this.containerId}-challenge-id">
                <input type="hidden" name="p_captcha_solution" id="${this.containerId}-solution">
            </div>
        `;
    }
}

// Global PCaptcha object for easy access
window.PCaptcha = {
    instances: {},

    init(containerId, options = {}) {
        console.log('P-CAPTCHA: Initializing with container ID:', containerId, 'options:', options);
        
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('P-CAPTCHA: Container not found:', containerId);
            return;
        }

        // Parse options
        const autoLoad = options.auto_load !== undefined ? options.auto_load : 
                        container.dataset.autoLoad === 'true';
        
        console.log('P-CAPTCHA: Auto load setting:', autoLoad);

        // Initialize container
        this.instances[containerId] = new PCaptchaWidget(containerId);

        // Auto-load challenge if enabled
        if (autoLoad) {
            console.log('P-CAPTCHA: Auto-loading challenge...');
            this.instances[containerId].loadChallenge();
        } else {
            console.log('P-CAPTCHA: Auto-load disabled, challenge will be loaded when needed');
        }
    },

    validate(containerId) {
        if (this.instances[containerId]) {
            this.instances[containerId].validateSolution();
        }
    },

    refresh(containerId) {
        if (this.instances[containerId]) {
            this.instances[containerId].refresh();
        }
    },

    selectSequenceChoice(containerId, choice) {
        if (this.instances[containerId]) {
            this.instances[containerId].selectSequenceChoice(choice);
        }
    },

    isVerified(containerId) {
        return this.instances[containerId] ? this.instances[containerId].isVerified() : false;
    },

    // Utility method for forms
    validateAllCaptchas() {
        return Object.values(this.instances).every(instance => instance.isVerified());
    }
};

// Auto-initialize any CAPTCHA containers on page load
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.p-captcha-container[data-auto-load="true"]');
    
    // Debug logging (only when APP_DEBUG is enabled)
    if (window.pCaptchaDebug && window.pCaptchaDebug.enabled) {
        console.log('P-CAPTCHA: Auto-initializing containers', {
            container_count: containers.length,
            containers: Array.from(containers).map(c => c.id)
        });
    }
    
    containers.forEach(container => {
        PCaptcha.init(container.id);
    });

    // Handle form submissions and middleware responses
    setupFormHandling();
});

// Setup form submission handling for middleware responses
function setupFormHandling() {
    // Intercept all form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        
        // Skip if it's not a regular form submission (e.g., AJAX)
        if (form.hasAttribute('data-ajax') || form.hasAttribute('data-no-captcha-handling')) {
            return;
        }

        // Store original submit handler
        const originalSubmit = form.onsubmit;
        
        form.onsubmit = async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.status === 422) {
                    const data = await response.json();
                    
                    // Check if visual CAPTCHA is required
                    if (data.visual_captcha_required) {
                        console.log('P-CAPTCHA: Visual CAPTCHA required by middleware');
                        
                        // Find CAPTCHA container in the form
                        const captchaContainer = form.querySelector('.p-captcha-container');
                        if (captchaContainer) {
                            const containerId = captchaContainer.id;
                            
                            // Initialize CAPTCHA if not already done
                            if (!PCaptcha.instances[containerId]) {
                                PCaptcha.init(containerId);
                            }
                            
                            // Show the CAPTCHA
                            captchaContainer.style.display = 'block';
                            captchaContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            
                            // Show error message
                            if (data.message) {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'alert alert-danger';
                                errorDiv.textContent = data.message;
                                form.insertBefore(errorDiv, form.firstChild);
                            }
                            
                            return false;
                        }
                    }
                    
                    // Handle other validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.parentNode.querySelector('.invalid-feedback') || 
                                               document.createElement('div');
                                feedback.className = 'invalid-feedback';
                                feedback.textContent = data.errors[field][0];
                                if (!input.parentNode.querySelector('.invalid-feedback')) {
                                    input.parentNode.appendChild(feedback);
                                }
                            }
                        });
                    }
                    
                    return false;
                }
                
                if (response.ok) {
                    // Success - submit the form normally
                    if (originalSubmit) {
                        return originalSubmit.call(form, e);
                    } else {
                        // For successful responses, we might want to redirect or show success message
                        const data = await response.json();
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else if (data.message) {
                            alert(data.message);
                        }
                    }
                }
                
            } catch (error) {
                console.error('P-CAPTCHA: Form submission error', error);
            }
        };
    });
}
