/**
 * P-CAPTCHA Frontend JavaScript
 * Handles all CAPTCHA interactions and validations
 */
class PCaptchaWidget {
    constructor(containerId) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.currentChallenge = null;
        this.solution = {};
        this.verified = false;

        if (!this.container) {
            console.error(`P-CAPTCHA: Container with ID '${containerId}' not found`);
            return;
        }

        this.init();
    }

    async init() {
        this.setupElements();
        await this.loadChallenge();
    }

    setupElements() {
        this.instructionsEl = this.container.querySelector(`#${this.containerId}-instructions`);
        this.challengeEl = this.container.querySelector(`#${this.containerId}-challenge`);
        this.statusEl = this.container.querySelector(`#${this.containerId}-status`);
        this.validateBtn = this.container.querySelector(`#${this.containerId}-validate`);
        this.challengeIdInput = this.container.querySelector(`#${this.containerId}-challenge-id`);
        this.solutionInput = this.container.querySelector(`#${this.containerId}-solution`);
    }

    async loadChallenge() {
        try {
            this.showLoading();

            const response = await fetch('/p-captcha/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            const data = await response.json();

            if (data.success) {
                this.currentChallenge = data.challenge;
                this.challengeIdInput.value = this.currentChallenge.id;
                this.renderChallenge();
            } else {
                this.showError('Failed to load CAPTCHA challenge');
            }

        } catch (error) {
            console.error('P-CAPTCHA: Failed to load challenge', error);
            this.showError('Network error. Please try again.');
        }
    }

    renderChallenge() {
        if (!this.currentChallenge) return;

        this.instructionsEl.textContent = this.currentChallenge.instructions;

        switch (this.currentChallenge.type) {
            case 'beam_alignment':
                this.renderBeamAlignment();
                break;
            case 'sequence_complete':
                this.renderSequenceComplete();
                break;
            default:
                this.showError('Unknown challenge type');
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
        const canvas = this.container.querySelector(`#${this.containerId}-beam-canvas`);
        const source = this.container.querySelector(`#${this.containerId}-beam-source`);
        const target = this.container.querySelector(`#${this.containerId}-beam-target`);

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
        const source = this.container.querySelector(`#${this.containerId}-beam-source`);
        const target = this.container.querySelector(`#${this.containerId}-beam-target`);

        if (!source || !target) return;

        const sourceX = parseInt(source.style.left) + 15; // Center of source
        const sourceY = parseInt(source.style.top) + 15;
        const targetX = parseInt(target.style.left) + 15; // Center of target
        const targetY = parseInt(target.style.top) + 15;

        const distance = Math.sqrt(Math.pow(targetX - sourceX, 2) + Math.pow(targetY - sourceY, 2));
        const angle = Math.atan2(targetY - sourceY, targetX - sourceX) * 180 / Math.PI;

        let beamLine = this.container.querySelector(`#${this.containerId}-beam-line`);
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
        const source = this.container.querySelector(`#${this.containerId}-beam-source`);
        const target = this.container.querySelector(`#${this.containerId}-beam-target`);
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
            this.showStatus('Beam aligned! Click Validate to continue.', 'success');
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
        this.container.querySelectorAll('.sequence-choice').forEach(el => {
            el.classList.remove('selected');
        });

        // Select new choice
        const choiceEl = this.container.querySelector(`[data-choice="${choice}"]`);
        if (choiceEl) {
            choiceEl.classList.add('selected');
            this.solution = { answer: parseInt(choice) };
            this.validateBtn.disabled = false;
        }
    }

    async validateSolution() {
        if (!this.currentChallenge || Object.keys(this.solution).length === 0) {
            this.showError('Please complete the challenge first');
            return;
        }

        try {
            this.validateBtn.disabled = true;
            this.showStatus('Validating...', 'info');

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
                this.showStatus('CAPTCHA verified successfully!', 'success');

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
                this.showError(data.message || 'Invalid solution. Please try again.');
                this.validateBtn.disabled = false;
            }

        } catch (error) {
            console.error('P-CAPTCHA: Validation failed', error);
            this.showError('Network error. Please try again.');
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
                <div>Loading challenge...</div>
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
}

// Global PCaptcha object for easy access
window.PCaptcha = {
    instances: {},

    init(containerId) {
        if (!this.instances[containerId]) {
            this.instances[containerId] = new PCaptchaWidget(containerId);
        }
        return this.instances[containerId];
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
    containers.forEach(container => {
        PCaptcha.init(container.id);
    });
});
