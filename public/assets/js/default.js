/**
 * Lighthouse Framework - Default JavaScript
 * Minimal HTMX-like behavior for forms and navigation
 */


(function () {
    'use strict';

    /**
     * Theme toggle (light/dark)
     */
    function initThemeToggle() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.body.setAttribute('data-theme', storedTheme);
        } else if (prefersDark) {
            document.body.setAttribute('data-theme', 'dark');
        }

        // Only add listeners (buttons are in HTML)
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                const current = document.body.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.body.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            });
        }

        const rtlToggle = document.getElementById('rtl-toggle');
        if (rtlToggle) {
            rtlToggle.addEventListener('click', function () {
                const html = document.documentElement;
                if (html.getAttribute('dir') === 'rtl') {
                    html.setAttribute('dir', 'ltr');
                    localStorage.setItem('dir', 'ltr');
                } else {
                    html.setAttribute('dir', 'rtl');
                    localStorage.setItem('dir', 'rtl');
                }
            });
        }

        // Restore RTL state from localStorage
        const storedDir = localStorage.getItem('dir');
        if (storedDir) {
            document.documentElement.setAttribute('dir', storedDir);
        }
    }
    /**
     * Dropdown toggle
     */
    function initDropdowns() {
        document.querySelectorAll('.dropdown-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const dropdown = btn.closest('.dropdown');
                document.querySelectorAll('.dropdown.open').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open');
            });
        });
        document.addEventListener('click', function () {
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        });
    }

    /**
     * Accordion toggle
     */
    function initAccordions() {
        document.querySelectorAll('.accordion-header').forEach(function (header) {
            header.addEventListener('click', function () {
                const item = header.closest('.accordion-item');
                const accordion = item.parentElement;
                if (!item.classList.contains('open')) {
                    accordion.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('open'));
                }
                item.classList.toggle('open');
            });
            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    header.click();
                }
            });
        });
    }

    /**
     * Initialize hamburger menu toggle
     */
    function initMenuToggle() {
        const toggle = document.getElementById('menu-toggle');
        const nav = document.querySelector('nav');

        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('active');
            });

            // Close menu when a link is clicked
            nav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function () {
                    nav.classList.remove('active');
                });
            });

            // Close menu on outside click
            document.addEventListener('click', function (e) {
                if (!toggle.contains(e.target) && !nav.contains(e.target)) {
                    nav.classList.remove('active');
                }
            });
        }
    }

    /**
     * Submit form via AJAX
     *
     * Usage: <form data-ajax="true">
     */
    function initAjaxForms() {
        document.addEventListener('submit', function (e) {
            const form = e.target;

            if (form.dataset.ajax !== 'true') {
                return;
            }

            e.preventDefault();

            const method = form.method || 'POST';
            const action = form.action || window.location.href;
            const formData = new FormData(form);

            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : '';

            // Show loading state
            if (button) {
                button.disabled = true;
                button.textContent = 'Loading...';
            }

            fetch(action, {
                method: method,
                body: formData,
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Replace form or show success message
                    const container = form.dataset.target || form.parentElement;
                    if (container.dataset.target) {
                        document.querySelector(container.dataset.target).innerHTML = html;
                    } else {
                        container.innerHTML = html;
                    }

                    // Dispatch custom event
                    document.dispatchEvent(new CustomEvent('ajax:success', { detail: { html } }));
                })
                .catch(error => {
                    console.error('Form submission error:', error);

                    // Show error alert
                    const alert = document.createElement('div');
                    alert.className = 'alert danger';
                    alert.textContent = 'An error occurred: ' + error.message;
                    form.parentElement.insertBefore(alert, form);

                    // Dispatch custom event
                    document.dispatchEvent(new CustomEvent('ajax:error', { detail: { error } }));
                })
                .finally(() => {
                    // Reset button
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
        });
    }

    /**
     * Intercept link clicks for AJAX navigation
     *
     * Usage: <a href="/page" data-ajax="true">Link</a>
     */
    function initAjaxLinks() {
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[data-ajax="true"]');

            if (!link) {
                return;
            }

            e.preventDefault();

            const target = link.dataset.target || 'main';
            const url = link.href;

            // Show loading state
            const container = document.querySelector(target);
            if (container) {
                container.style.opacity = '0.6';
                container.style.pointerEvents = 'none';
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    if (container) {
                        container.innerHTML = html;
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                    }

                    // Update URL without reload
                    window.history.pushState({ url }, '', url);

                    // Dispatch custom event
                    document.dispatchEvent(new CustomEvent('ajax:load', { detail: { html } }));
                })
                .catch(error => {
                    console.error('Link navigation error:', error);
                    if (container) {
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                    }
                });
        });
    }

    /**
     * Add common keyboard shortcuts
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Ctrl/Cmd + Enter to submit forms
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = e.target.closest('form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }

            // Escape to close modals/menus
            if (e.key === 'Escape') {
                const nav = document.querySelector('nav.active');
                if (nav) {
                    nav.classList.remove('active');
                }
            }
        });
    }

    /**
     * Initialize all components on DOM ready
     */

    function init() {
        initThemeToggle();
        initMenuToggle();
        initAjaxForms();
        initAjaxLinks();
        initKeyboardShortcuts();
        initDropdowns();
        initAccordions();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.Lighthouse = {
        init: init,
        initMenuToggle: initMenuToggle,
        initAjaxForms: initAjaxForms,
        initAjaxLinks: initAjaxLinks,
    };
})();
