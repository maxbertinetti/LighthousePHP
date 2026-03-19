(function () {
    'use strict';

    const REQUEST_ATTRS = ['get', 'post', 'put', 'patch', 'delete'];
    const sockets = new WeakMap();
    const sseStreams = new WeakMap();

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function dispatch(element, name, detail) {
        return element.dispatchEvent(new CustomEvent('lh:' + name, {
            bubbles: true,
            cancelable: true,
            detail: detail || {},
        }));
    }

    function setMenuState(nav, toggle, open) {
        nav.setAttribute('data-menu-open', open ? 'true' : 'false');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function initMenuToggle() {
        const nav = document.querySelector('body > header nav');
        const toggle = document.getElementById('menu-toggle');

        if (!nav || !toggle) {
            return;
        }

        setMenuState(nav, toggle, false);

        toggle.addEventListener('click', function () {
            const isOpen = nav.getAttribute('data-menu-open') === 'true';
            setMenuState(nav, toggle, !isOpen);
        });

        nav.querySelectorAll('ul a').forEach(function (link) {
            link.addEventListener('click', function () {
                setMenuState(nav, toggle, false);
            });
        });

        document.addEventListener('click', function (event) {
            if (!nav.contains(event.target)) {
                setMenuState(nav, toggle, false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setMenuState(nav, toggle, false);
            }
        });
    }

    function getRequestConfig(element) {
        for (const method of REQUEST_ATTRS) {
            const key = 'data-' + method;
            if (element.hasAttribute(key)) {
                return {
                    method: method.toUpperCase(),
                    url: element.getAttribute(key),
                };
            }
        }

        if (element.tagName === 'FORM' && element.hasAttribute('data-ajax')) {
            return {
                method: (element.getAttribute('method') || 'GET').toUpperCase(),
                url: element.getAttribute('action') || window.location.href,
            };
        }

        return null;
    }

    function resolveTarget(element, expression) {
        const value = expression || element.getAttribute('data-target');

        if (!value || value === 'this') {
            return element;
        }

        if (value === 'body') {
            return document.body;
        }

        if (value === 'main') {
            return document.querySelector('main');
        }

        if (value.indexOf('closest ') === 0) {
            return element.closest(value.slice(8).trim());
        }

        if (value.indexOf('find ') === 0) {
            return element.querySelector(value.slice(5).trim());
        }

        if (value === 'next') {
            return element.nextElementSibling;
        }

        if (value.indexOf('next ') === 0) {
            let next = element.nextElementSibling;
            const selector = value.slice(5).trim();

            while (next) {
                if (next.matches(selector)) {
                    return next;
                }
                next = next.nextElementSibling;
            }

            return null;
        }

        if (value === 'prev') {
            return element.previousElementSibling;
        }

        if (value.indexOf('prev ') === 0) {
            let prev = element.previousElementSibling;
            const selector = value.slice(5).trim();

            while (prev) {
                if (prev.matches(selector)) {
                    return prev;
                }
                prev = prev.previousElementSibling;
            }

            return null;
        }

        return document.querySelector(value);
    }

    function getSwapStrategy(element, fallback) {
        return element.getAttribute('data-swap') || fallback || 'innerHTML';
    }

    function getTransitionConfig(element) {
        return {
            name: element.getAttribute('data-transition') || 'none',
            duration: Number(element.getAttribute('data-duration') || 180),
        };
    }

    function applySwap(target, html, strategy) {
        if (!target) {
            return;
        }

        switch (strategy) {
            case 'outerHTML':
                target.outerHTML = html;
                break;
            case 'beforebegin':
                target.insertAdjacentHTML('beforebegin', html);
                break;
            case 'afterbegin':
                target.insertAdjacentHTML('afterbegin', html);
                break;
            case 'beforeend':
            case 'append':
                target.insertAdjacentHTML('beforeend', html);
                break;
            case 'afterend':
                target.insertAdjacentHTML('afterend', html);
                break;
            case 'text':
                target.textContent = html;
                break;
            default:
                target.innerHTML = html;
                break;
        }
    }

    function animateSwap(target, html, strategy, transition) {
        if (!target || transition.name === 'none' || strategy !== 'innerHTML') {
            applySwap(target, html, strategy);
            return;
        }

        const originalTransition = target.style.transition;
        const originalOpacity = target.style.opacity;
        const originalTransform = target.style.transform;
        const duration = transition.duration;
        const easing = 'ease';
        const withSlide = transition.name === 'slide';

        target.style.transition = 'opacity ' + duration + 'ms ' + easing + (withSlide ? ', transform ' + duration + 'ms ' + easing : '');
        target.style.opacity = '0';

        if (withSlide) {
            target.style.transform = 'translateY(0.5rem)';
        }

        window.setTimeout(function () {
            applySwap(target, html, strategy);
            target.style.opacity = '1';
            target.style.transform = 'translateY(0)';

            window.setTimeout(function () {
                target.style.transition = originalTransition;
                target.style.opacity = originalOpacity;
                target.style.transform = originalTransform;
            }, duration);
        }, duration);
    }

    function setBusyState(element, target, busy) {
        const value = busy ? 'true' : 'false';

        element.setAttribute('aria-busy', value);

        if (target && target !== element) {
            target.setAttribute('aria-busy', value);
        }
    }

    function buildRequest(element, config) {
        const url = new URL(config.url, window.location.href);
        const options = {
            method: config.method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        if (element.tagName === 'FORM') {
            const formData = new FormData(element);

            if (config.method === 'GET') {
                formData.forEach(function (value, key) {
                    url.searchParams.append(key, value);
                });
            } else {
                options.body = formData;
            }
        }

        return {
            url: url.toString(),
            options: options,
        };
    }

    function shouldHandleTrigger(element, eventName) {
        const trigger = element.getAttribute('data-trigger');

        if (!trigger) {
            return element.tagName === 'FORM' ? eventName === 'submit' : eventName === 'click';
        }

        return trigger
            .split(/[\s,]+/)
            .filter(Boolean)
            .indexOf(eventName) !== -1;
    }

    async function runRequest(element) {
        const config = getRequestConfig(element);

        if (!config) {
            return;
        }

        const target = resolveTarget(element);
        const indicator = resolveTarget(element, element.getAttribute('data-indicator'));
        const swap = getSwapStrategy(element);
        const transition = getTransitionConfig(element);
        const request = buildRequest(element, config);

        if (!dispatch(element, 'before-request', {
            config: config,
            request: request,
            target: target,
        })) {
            return;
        }

        try {
            setBusyState(element, indicator || target, true);

            const response = await fetch(request.url, request.options);
            const html = await response.text();

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            dispatch(element, 'before-swap', {
                html: html,
                target: target,
                swap: swap,
            });

            animateSwap(target, html, swap, transition);

            if (element.getAttribute('data-push-url') === 'true') {
                window.history.pushState({}, '', request.url);
            }

            dispatch(element, 'after-request', {
                response: response,
                html: html,
                target: target,
            });
        } catch (error) {
            dispatch(element, 'request-error', {
                error: error,
                target: target,
            });
        } finally {
            setBusyState(element, indicator || target, false);
        }
    }

    function handleRequestEvent(event) {
        const selector = '[data-get],[data-post],[data-put],[data-patch],[data-delete],form[data-ajax]';
        const element = event.target.closest(selector);

        if (!element || !shouldHandleTrigger(element, event.type)) {
            return;
        }

        if (event.type === 'click' && (element.tagName === 'A' || element.tagName === 'BUTTON')) {
            event.preventDefault();
        }

        if (event.type === 'submit') {
            event.preventDefault();
        }

        runRequest(element);
    }

    function handleLoadTriggers() {
        qsa('[data-trigger~="load"]').forEach(function (element) {
            if (getRequestConfig(element)) {
                runRequest(element);
            }
        });
    }

    function connectSSE(element) {
        if (sseStreams.has(element)) {
            return;
        }

        const url = element.getAttribute('data-sse');

        if (!url) {
            return;
        }

        const stream = new EventSource(url);
        const eventName = element.getAttribute('data-sse-event');
        const target = resolveTarget(element);
        const swap = getSwapStrategy(element, 'innerHTML');
        const transition = getTransitionConfig(element);
        const handler = function (event) {
            animateSwap(target, event.data, swap, transition);
            dispatch(element, 'sse-message', {
                message: event.data,
                target: target,
            });
        };

        if (eventName) {
            stream.addEventListener(eventName, handler);
        } else {
            stream.onmessage = handler;
        }

        stream.onerror = function (error) {
            dispatch(element, 'sse-error', { error: error });
        };

        sseStreams.set(element, stream);
    }

    function serializeForm(form, format) {
        const formData = new FormData(form);

        if (format === 'form') {
            return new URLSearchParams(formData).toString();
        }

        if (format === 'json') {
            const object = {};

            formData.forEach(function (value, key) {
                if (Object.prototype.hasOwnProperty.call(object, key)) {
                    object[key] = [].concat(object[key], value);
                } else {
                    object[key] = value;
                }
            });

            return JSON.stringify(object);
        }

        return form.getAttribute('data-ws-payload') || '';
    }

    function resolveSocketHost(element) {
        const ref = element.getAttribute('data-ws-send');

        if (!ref || ref === 'closest') {
            return element.closest('[data-ws]');
        }

        return document.querySelector(ref);
    }

    function connectSocket(element) {
        if (sockets.has(element)) {
            return sockets.get(element);
        }

        const url = element.getAttribute('data-ws');

        if (!url) {
            return null;
        }

        const socket = new WebSocket(url);
        const target = resolveTarget(element);
        const swap = getSwapStrategy(element, 'innerHTML');
        const transition = getTransitionConfig(element);

        socket.addEventListener('message', function (event) {
            animateSwap(target, event.data, swap, transition);
            dispatch(element, 'ws-message', {
                message: event.data,
                target: target,
            });
        });

        socket.addEventListener('error', function (error) {
            dispatch(element, 'ws-error', { error: error });
        });

        sockets.set(element, socket);
        return socket;
    }

    function sendSocketMessage(element) {
        const host = resolveSocketHost(element);

        if (!host) {
            return;
        }

        const socket = connectSocket(host);

        if (!socket || socket.readyState !== WebSocket.OPEN) {
            return;
        }

        if (element.tagName === 'FORM') {
            const format = element.getAttribute('data-ws-format') || 'json';
            socket.send(serializeForm(element, format));
            return;
        }

        socket.send(
            element.getAttribute('data-ws-payload') ||
            element.value ||
            element.textContent.trim()
        );
    }

    function handleSocketSend(event) {
        const submitter = event.target.closest('[data-ws-send]');

        if (!submitter) {
            return;
        }

        if (submitter.tagName === 'FORM') {
            if (!shouldHandleTrigger(submitter, event.type)) {
                return;
            }

            event.preventDefault();
            sendSocketMessage(submitter);
            return;
        }

        if (event.type === 'click' && shouldHandleTrigger(submitter, event.type)) {
            event.preventDefault();
            sendSocketMessage(submitter);
        }
    }

    function initRealtime() {
        qsa('[data-sse]').forEach(connectSSE);
        qsa('[data-ws]').forEach(connectSocket);
    }

    function initDeclarativeRequests() {
        document.addEventListener('click', handleRequestEvent);
        document.addEventListener('submit', handleRequestEvent);
        document.addEventListener('change', handleRequestEvent);
        document.addEventListener('input', handleRequestEvent);

        document.addEventListener('click', handleSocketSend);
        document.addEventListener('submit', handleSocketSend);
    }

    function init() {
        initMenuToggle();
        initDeclarativeRequests();
        initRealtime();
        handleLoadTriggers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.Lighthouse = {
        init: init,
        request: runRequest,
        connectSSE: connectSSE,
        connectSocket: connectSocket,
    };
})();
