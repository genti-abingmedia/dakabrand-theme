(function () {
    'use strict';

    var config = window.StaticBridgeVideoPopup || {};
    var videoUrl = config.videoUrl;
    var storageKey = config.storageKey || 'daka_back_to_school_popup';
    var maxShowsPerDay = Number(config.maxShowsPerDay) || 2;
    var hoursBetweenShows = Number(config.hoursBetweenShows) || 5;
    var showDelay = Number(config.showDelay) || 1000;

    if (!videoUrl || document.querySelector('.daka-video-popup')) return;

    function localDate() {
        var now = new Date();
        var offset = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - offset).toISOString().slice(0, 10);
    }

    function readPopupData(today) {
        var saved;
        try {
            saved = JSON.parse(localStorage.getItem(storageKey));
        } catch (error) {
            return { date: today, count: 0, lastShown: null };
        }
        return saved && saved.date === today
            ? { date: today, count: Number(saved.count) || 0, lastShown: Number(saved.lastShown) || null }
            : { date: today, count: 0, lastShown: null };
    }

    function canShow(data, timestamp) {
        if (data.count >= maxShowsPerDay) return false;
        return data.count !== 1 || !data.lastShown || timestamp - data.lastShown >= hoursBetweenShows * 60 * 60 * 1000;
    }

    function init() {
        var today = localDate();
        var popupData = readPopupData(today);
        var popup;
        var closeButton;
        var priorOverflow;
        var trigger;
        var closed = false;

        if (!canShow(popupData, Date.now())) return;

        popup = document.createElement('div');
        popup.className = 'daka-video-popup';
        popup.innerHTML = '<div class="daka-video-popup__content" role="dialog" aria-modal="true" aria-label="' + escapeHtml(config.dialogLabel || 'Daka Outlet Back to School') + '">' +
            '<button class="daka-video-popup__close" type="button" aria-label="' + escapeHtml(config.closeLabel || 'Close video popup') + '">&times;</button>' +
            '<video class="daka-video-popup__video" muted autoplay playsinline preload="auto"><source src="' + escapeAttribute(videoUrl) + '" type="video/mp4"></video></div>';
        closeButton = popup.querySelector('.daka-video-popup__close');

        function openPopup() {
            var video;
            if (closed || popup.parentNode) return;
            document.body.appendChild(popup);
            trigger = document.activeElement;
            priorOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            popupData.count += 1;
            popupData.lastShown = Date.now();
            try { localStorage.setItem(storageKey, JSON.stringify(popupData)); } catch (error) {}
            requestAnimationFrame(function () { popup.classList.add('is-visible'); });
            closeButton.focus();
            video = popup.querySelector('.daka-video-popup__video');
            video.muted = true;
            video.defaultMuted = true;
            video.volume = 0;
            video.play().catch(function () {});
        }

        function closePopup() {
            var video;
            if (closed) return;
            closed = true;
            video = popup.querySelector('.daka-video-popup__video');
            if (video) video.pause();
            popup.classList.remove('is-visible');
            document.body.style.overflow = priorOverflow || '';
            if (trigger && typeof trigger.focus === 'function') trigger.focus();
            window.setTimeout(function () { if (popup.parentNode) popup.remove(); }, 300);
        }

        closeButton.addEventListener('click', closePopup);
        popup.querySelector('.daka-video-popup__video').addEventListener('ended', closePopup);
        popup.addEventListener('click', function (event) { if (event.target === popup) closePopup(); });
        document.addEventListener('keydown', function onKeydown(event) {
            if (event.key === 'Escape' && popup.classList.contains('is-visible')) {
                closePopup();
                document.removeEventListener('keydown', onKeydown);
            }
        });
        window.setTimeout(openPopup, showDelay);
    }

    function escapeHtml(value) { return String(value).replace(/[&<>"']/g, function (character) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]; }); }
    function escapeAttribute(value) { return escapeHtml(value); }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
}());
