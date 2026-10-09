define([], function () {
    'use strict';

    var STORAGE_KEY = 'webjump-haunted-mode',
        TOGGLE_SELECTOR = '[data-haunted-mode-toggle]',
        ASSETS_ATTR = 'data-haunted-assets';

    function readState() {
        try {
            return localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function writeState(enabled) {
        try {
            if (enabled) {
                localStorage.setItem(STORAGE_KEY, '1');
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            // Storage indisponível (modo privado): o estado vale só para a página atual.
        }
    }

    function applyState(root, toggle, enabled) {
        root.classList.toggle('haunted-mode', enabled);
        toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
        writeState(enabled);
    }

    function prefersReducedMotion() {
        return typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function toggleState(root, toggle) {
        var enabled = !root.classList.contains('haunted-mode');

        if (typeof document.startViewTransition !== 'function' || prefersReducedMotion()) {
            applyState(root, toggle, enabled);
            return;
        }

        // Bloqueia o botão durante a transição para não iniciar uma sobre a outra.
        toggle.disabled = true;
        var release = function () {
            toggle.disabled = false;
        };

        try {
            document.startViewTransition(function () {
                applyState(root, toggle, enabled);
            }).finished.then(release, release);
        } catch (e) {
            applyState(root, toggle, enabled);
            release();
        }
    }

    function preloadAssets(toggle) {
        var raw = toggle.getAttribute(ASSETS_ATTR);

        if (!raw) {
            return;
        }

        try {
            JSON.parse(raw).forEach(function (url) {
                var image = new Image();
                image.src = url;
            });
        } catch (e) {
            // Atributo inválido: o navegador carrega as imagens sob demanda mesmo.
        }
    }

    function schedulePreload(toggle) {
        var run = function () {
            preloadAssets(toggle);
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(run, { timeout: 3000 });
        } else {
            window.setTimeout(run, 1000);
        }
    }

    function start() {
        var root = document.documentElement,
            toggle = document.querySelector(TOGGLE_SELECTOR);

        if (!toggle) {
            return;
        }

        applyState(root, toggle, readState());
        schedulePreload(toggle);

        toggle.addEventListener('click', function () {
            toggleState(root, toggle);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    return { start: start };
});
