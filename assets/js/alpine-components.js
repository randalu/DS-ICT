document.addEventListener('alpine:init', () => {

Alpine.data('formWizard', (config) => ({
    config: config,
    step: 1,
    totalSteps: config?.steps?.length || 4,
    errors: {},
    touched: {},
    ...(config?.initialData || {}),

    init() {
        if (config?.steps) {
            config.steps.forEach(step => {
                if (step.fields) {
                    step.fields.forEach(field => {
                        if (this[field.name] === undefined) {
                            this[field.name] = field.type === 'pills' ? [] : '';
                        } else if (field.type === 'pills' && typeof this[field.name] === 'string') {
                            this[field.name] = this[field.name] ? this[field.name].split(',').map(s => s.trim()) : [];
                        }
                    });
                }
            });
        }
    },

    get progress() {
        return (this.step / this.totalSteps) * 100;
    },

    get stepConfig() {
        return config?.steps?.[this.step - 1] || {};
    },

    get stepFields() {
        return this.stepConfig.fields || [];
    },

    get stepValid() {
        if (this.stepFields.length === 0) return true;
        for (let i = 0; i < this.stepFields.length; i++) {
            const field = this.stepFields[i];
            if (!field.required) continue;
            const val = this[field.name];
            if (val === null || val === undefined || String(val).trim() === '') return false;
        }
        if (this.step === 1 && this.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) return false;
        return true;
    },

    validateField(field) {
        if (field === 'name' && (!this.name || this.name.trim() === '')) {
            this.errors.name = 'Name is required.';
        } else if (field === 'name') {
            delete this.errors.name;
        }
        if (field === 'email') {
            if (!this.email || this.email.trim() === '') {
                this.errors.email = 'Email is required.';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                this.errors.email = 'Please enter a valid email address.';
            } else {
                delete this.errors.email;
            }
        }
        if (field === 'message' && (!this.message || this.message.trim() === '')) {
            this.errors.message = 'Message is required.';
        } else if (field === 'message') {
            delete this.errors.message;
        }
    },

    blurField(field) {
        this.touched[field] = true;
        this.validateField(field);
    },

    validateStep() {
        const fields = this.stepFields;
        for (let i = 0; i < fields.length; i++) {
            this.touched[fields[i]] = true;
            this.validateField(fields[i]);
        }
        return !fields.some(f => this.errors[f]);
    },

    nextStep() {
        if (this.validateStep() && this.step < this.totalSteps) {
            this.step++;
            this.$nextTick(() => {
                const currentStep = this.$el.querySelector('.wizard-step-content:not([style*="display: none"])');
                if (currentStep) {
                    const firstInput = currentStep.querySelector('input, textarea, select');
                    if (firstInput) firstInput.focus();
                }
            });
        }
    },

    prevStep() {
        if (this.step > 1) this.step--;
    },

    submitForm() {
        const allFields = ['name', 'email', 'message'];
        for (let i = 0; i < allFields.length; i++) {
            this.touched[allFields[i]] = true;
            this.validateField(allFields[i]);
        }
        const hasErrors = allFields.some(f => this.errors[f]);
        if (!hasErrors) {
            this.$refs.form.submit();
        }
    },

    toggleInterest(interest) {
        if (!Array.isArray(this.interests)) this.interests = [];
        const idx = this.interests.indexOf(interest);
        if (idx > -1) this.interests.splice(idx, 1);
        else this.interests.push(interest);
    },

    hasInterest(interest) {
        return Array.isArray(this.interests) && this.interests.indexOf(interest) > -1;
    },

    renderProgress() {
        return `<div class="wizard-progress" role="progressbar" aria-valuenow="${this.progress}" aria-valuemin="0" aria-valuemax="100" style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden">
            <div class="wizard-progress-bar" style="width:${this.progress}%;height:100%;background:#166534;transition:width .3s ease"></div>
        </div>`;
    }
}));

Alpine.data('itineraryScroller', () => ({
    track: null,
    autoSlideInterval: null,
    init() {
        this.track = this.$refs.track;
        if (!this.track) return;
        this.autoSlideInterval = setInterval(() => {
            if (!this.track || this.track.matches(':hover')) return;
            const slides = this.track.querySelectorAll('.itin-c-slide');
            if (slides.length < 2) return;
            const slideWidth = slides[0].offsetWidth + parseInt(getComputedStyle(this.track).gap || '0', 10);
            const maxScroll = this.track.scrollWidth - this.track.clientWidth;
            if (this.track.scrollLeft + slideWidth >= maxScroll) {
                this.track.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                this.track.scrollBy({ left: slideWidth, behavior: 'smooth' });
            }
        }, 5000);
    },
    prev() {
        if (!this.track) return;
        const slide = this.track.querySelector('.itin-c-slide');
        if (!slide) return;
        const amount = slide.offsetWidth + (parseInt(getComputedStyle(this.track).gap || '0', 10));
        this.track.scrollBy({ left: -amount, behavior: 'smooth' });
    },
    next() {
        if (!this.track) return;
        const slide = this.track.querySelector('.itin-c-slide');
        if (!slide) return;
        const amount = slide.offsetWidth + (parseInt(getComputedStyle(this.track).gap || '0', 10));
        this.track.scrollBy({ left: amount, behavior: 'smooth' });
    },
    destroy() {
        if (this.autoSlideInterval) clearInterval(this.autoSlideInterval);
    }
}));

Alpine.data('durationScroller', () => ({
    init() {
        setInterval(() => {
            const scroller = this.$refs.scroller;
            if (!scroller || scroller.matches(':hover')) return;
            scroller.scrollBy({ left: 120, behavior: 'smooth' });
            if (scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth) {
                setTimeout(() => scroller.scrollTo({ left: 0, behavior: 'smooth' }), 800);
            }
        }, 2800);
    }
}));

Alpine.data('themeRotator', () => ({
    current: {},
    themes: [
        { question: 'Looking for a short escape?', answer: 'Short Tours' },
        { question: 'Want a classic Sri Lanka experience?', answer: 'Classic Tours' },
        { question: 'Interested in culture & wildlife?', answer: 'Culture + Safari' }
    ],
    init() {
        this.current = this.themes[0];
        let i = 0;
        setInterval(() => {
            i = (i + 1) % this.themes.length;
            this.current = this.themes[i];
        }, 3000);
    }
}));

Alpine.data('cardReveal', () => ({
    visible: false,
    observer: null,
    init() {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.visible = true;
                    this.observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        this.observer.observe(this.$el);
    },
    destroy() {
        if (this.observer) this.observer.disconnect();
    }
}));

Alpine.data('backToTop', () => ({
    visible: false,
    init() {
        window.addEventListener('scroll', () => {
            this.visible = window.scrollY > 200;
        }, { passive: true });
    },
    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}));

Alpine.data('floatingPlanMenu', () => ({
    expanded: false,
    showPlanTrip: false,
    init() {
        const path = window.location.pathname;
        const showOnPages = ['/itineraries/', '/tours/', '/day-tours/'];
        this.showPlanTrip = showOnPages.some(p => path.startsWith(p));
    },
    toggle() {
        this.expanded = !this.expanded;
    }
}));

Alpine.data('breadcrumbNav', () => ({
    expanded: false,
    init() {
        this.$watch('expanded', () => {
            if (this.expanded) setTimeout(() => this.expanded = false, 3000);
        });
    }
}));

});