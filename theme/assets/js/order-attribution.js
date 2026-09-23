(function (root) {
    'use strict';

    // Static pages do not boot WordPress, so WooCommerce's normal
    // sourcebuster script cannot collect order-attribution data here. Keep the
    // equivalent, minimal session data in the browser and pass it to the Store
    // API only when an order is submitted.
    var storageKey = 'staticbridge_order_attribution_v1';
    var searchEngines = ['google.', 'bing.com', 'yahoo.', 'duckduckgo.com', 'baidu.com', 'yandex.', 'ecosia.org', 'ask.com', 'aol.com'];

    function cleanHost(value) { return String(value || '').toLowerCase().replace(/^www\./, ''); }
    function isSearchEngine(host) { return searchEngines.some(function (engine) { return host.indexOf(engine) !== -1; }); }
    function read() {
        try { return JSON.parse(root.sessionStorage.getItem(storageKey) || 'null'); } catch (error) { return null; }
    }
    function write(value) {
        try { root.sessionStorage.setItem(storageKey, JSON.stringify(value)); } catch (error) { /* Private-mode storage is optional. */ }
    }
    function externalReferrer() {
        if (!document.referrer) return null;
        try {
            var referrer = new URL(document.referrer);
            return cleanHost(referrer.hostname) === cleanHost(root.location.hostname) ? null : referrer;
        } catch (error) { return null; }
    }
    function campaign(location) {
        var params = location.searchParams;
        var values = {
            source: params.get('utm_source') || '', medium: params.get('utm_medium') || '', campaign: params.get('utm_campaign') || '',
            content: params.get('utm_content') || '', id: params.get('utm_id') || '', term: params.get('utm_term') || '',
            source_platform: params.get('utm_source_platform') || '', creative_format: params.get('utm_creative_format') || '',
            marketing_tactic: params.get('utm_marketing_tactic') || ''
        };
        return Object.values(values).some(Boolean) ? values : null;
    }
    function capture() {
        var current = read() || {};
        var location = new URL(root.location.href);
        var referrer = externalReferrer();
        var utm = campaign(location);
        var source = current.source_type ? null : { source_type: 'typein', source: '', referrer: '' };

        // Match WooCommerce's priority: campaigns and organic visits override
        // a prior source, while direct traffic never does.
        if (utm) source = { source_type: 'utm', source: utm.source, referrer: referrer ? referrer.href : '', utm: utm };
        else if (referrer && isSearchEngine(cleanHost(referrer.hostname))) source = { source_type: 'organic', source: cleanHost(referrer.hostname), referrer: referrer.href };
        else if (!current.source_type && referrer) source = { source_type: 'referral', source: cleanHost(referrer.hostname), referrer: referrer.href };

        if (source) {
            current.source_type = source.source_type;
            current.source = source.source;
            current.referrer = source.referrer;
            current.utm = source.utm || {};
        }
        current.entry = current.entry || (location.pathname + location.search);
        current.started_at = current.started_at || String(Date.now());
        current.pages = Number(current.pages || 0) + 1;
        current.session_count = Number(current.session_count || 0) + 1;
        current.user_agent = String(root.navigator.userAgent || '');
        write(current);
        return current;
    }
    function data() {
        var current = capture();
        var utm = current.utm || {};
        return {
            source_type: current.source_type || 'typein', referrer: current.referrer || '',
            utm_campaign: utm.campaign || '', utm_source: utm.source || current.source || '', utm_medium: utm.medium || '',
            utm_content: utm.content || '', utm_id: utm.id || '', utm_term: utm.term || '',
            utm_source_platform: utm.source_platform || '', utm_creative_format: utm.creative_format || '', utm_marketing_tactic: utm.marketing_tactic || '',
            session_entry: current.entry || '', session_start_time: current.started_at || '', session_pages: String(current.pages || 1),
            session_count: String(current.session_count || 1), user_agent: current.user_agent || ''
        };
    }
    root.StaticBridgeOrderAttribution = { data: data };
    capture();
}(window));
