@if ($ga4Config['enabled'])
    <script id="ga4-events-config" type="application/json">{!! json_encode($ga4Config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (() => {
            const readConfig = () => {
                const node = document.getElementById('ga4-events-config');

                if (!node) {
                    return {};
                }

                try {
                    return JSON.parse(node.textContent || '{}');
                } catch (_) {
                    return {};
                }
            };

            const config = readConfig();
            const queue = [];

            const print = (level, message, meta = null) => {
                if (!config.debug) {
                    return;
                }

                const parts = [config.consolePrefix || '[GA4 Events]', `[${level}]`, message];

                if (meta !== null) {
                    console.log(...parts, meta);

                    return;
                }

                console.log(...parts);
            };

            if (config.injectGtagScript && config.measurementId) {
                const src = `${config.gtagUrl}?id=${encodeURIComponent(config.measurementId)}`;
                if (!document.querySelector(`script[src="${src}"]`)) {
                    const script = document.createElement('script');
                    script.async = true;
                    script.src = src;
                    document.head.appendChild(script);
                }

                window.dataLayer = window.dataLayer || [];
                const gtagFunctionName = config.gtagFunctionName || 'gtag';
                window[gtagFunctionName] = window[gtagFunctionName] || function () {
                    dataLayer.push(arguments);
                };
                window[gtagFunctionName]('js', new Date());
                window[gtagFunctionName]('config', config.measurementId, {
                    send_page_view: config.autoPageView === true,
                });
            }

            const sanitizeName = (value) => {
                if (typeof value !== 'string') {
                    return '';
                }

                return value.trim();
            };

            const blockedKeys = Array.isArray(config.blockedParamKeys) ? config.blockedParamKeys : [];
            const reservedPrefixes = Array.isArray(config.reservedPrefixes) ? config.reservedPrefixes : [];

            const isBlockedParamKey = (key) => {
                if (blockedKeys.includes(key)) {
                    return true;
                }

                return reservedPrefixes.some((prefix) => key.startsWith(prefix));
            };

            const sanitizeParams = (params) => {
                if (params === null || typeof params !== 'object' || Array.isArray(params)) {
                    return { values: {}, errors: ['Event params must be an object.'] };
                }

                const maxParams = Number(config.maxParams || 25);
                const maxParamKeyLength = Number(config.maxParamKeyLength || 40);
                const maxParamValueLength = Number(config.maxParamValueLength || 100);
                const errors = [];
                const entries = Object.entries(params);
                const limitedEntries = entries.slice(0, maxParams);
                const values = {};

                if (entries.length > maxParams) {
                    errors.push('Event params exceed max allowed count.');
                }

                for (const [rawKey, rawValue] of limitedEntries) {
                    const key = String(rawKey).trim().replace(/\s+/g, '_');

                    if (!key.length) {
                        errors.push('Event param key cannot be empty.');
                        continue;
                    }

                    if (key.length > maxParamKeyLength) {
                        errors.push(`Event param key exceeds max length: ${key}`);
                        continue;
                    }

                    if (isBlockedParamKey(key)) {
                        errors.push(`Event param key is blocked: ${key}`);
                        continue;
                    }

                    if (typeof rawValue === 'string') {
                        values[key] = rawValue.slice(0, maxParamValueLength);
                        if (rawValue.length > maxParamValueLength) {
                            errors.push(`Event param value was truncated: ${key}`);
                        }
                        continue;
                    }

                    if (['number', 'boolean'].includes(typeof rawValue) || rawValue === null) {
                        values[key] = rawValue;
                        continue;
                    }

                    if (Array.isArray(rawValue) || (rawValue && typeof rawValue === 'object')) {
                        values[key] = rawValue;
                        continue;
                    }

                    errors.push(`Event param value type is not supported: ${key}`);
                }

                return { values, errors };
            };

            const normalizeRawPayload = (rawPayload) => {
                if (!rawPayload) {
                    return {};
                }

                if (Array.isArray(rawPayload)) {
                    if (rawPayload.length === 1 && rawPayload[0] && typeof rawPayload[0] === 'object') {
                        return rawPayload[0];
                    }

                    return {};
                }

                if (typeof rawPayload !== 'object') {
                    return {};
                }

                if ('name' in rawPayload || 'params' in rawPayload || 'options' in rawPayload) {
                    return rawPayload;
                }

                if ('payload' in rawPayload && rawPayload.payload && typeof rawPayload.payload === 'object') {
                    return rawPayload.payload;
                }

                if ('0' in rawPayload && rawPayload[0] && typeof rawPayload[0] === 'object') {
                    return rawPayload[0];
                }

                return rawPayload;
            };

            const validatePayload = (payload) => {
                const errors = [];
                const name = sanitizeName(payload && payload.name ? payload.name : '');
                const maxEventNameLength = Number(config.maxEventNameLength || 40);
                const patternString = String(config.allowedNamePattern || '').replace(/^\//, '').replace(/\/[gimsuy]*$/, '');
                const pattern = new RegExp(patternString || '^[a-zA-Z][a-zA-Z0-9_]*$');

                if (!name.length) {
                    errors.push('Event name is required.');
                }

                if (name.length > maxEventNameLength) {
                    errors.push('Event name exceeds max allowed length.');
                }

                if (name.length && !pattern.test(name)) {
                    errors.push('Event name does not match allowed pattern.');
                }

                const payloadParams = payload && payload.params ? payload.params : {};
                const payloadOptions = payload && payload.options ? payload.options : {};
                const { values: params, errors: paramErrors } = sanitizeParams(payloadParams);
                errors.push(...paramErrors);

                const options = {
                    ...(typeof config.defaultEventOptions === 'object' && config.defaultEventOptions ? config.defaultEventOptions : {}),
                    ...(typeof payloadOptions === 'object' && payloadOptions && !Array.isArray(payloadOptions) ? payloadOptions : {}),
                };

                return {
                    valid: errors.length === 0,
                    errors,
                    payload: {
                        name,
                        params,
                        options,
                    },
                };
            };

            const getGtag = () => {
                const fn = window[config.gtagFunctionName || 'gtag'];

                if (typeof fn === 'function') {
                    return fn;
                }

                return null;
            };

            const flushQueue = () => {
                const gtag = getGtag();

                if (!gtag || !queue.length) {
                    return;
                }

                while (queue.length) {
                    const item = queue.shift();
                    gtag('event', item.name, {
                        ...item.params,
                        ...item.options,
                    });
                    print('INFO', 'Queued event sent to gtag.', item);
                }
            };

            const sendToGtag = (payload, source) => {
                const gtag = getGtag();

                if (!gtag) {
                    const canQueue = config.queueUntilGtagReady === true && queue.length < Number(config.maxQueueSize || 100);
                    if (canQueue) {
                        queue.push(payload);
                        print('WARN', `gtag is not ready. Event queued from ${source}.`, payload);
                        return { sent: false, queued: true, reason: 'gtag_not_ready' };
                    }

                    print('ERROR', `gtag is not ready. Event dropped from ${source}.`, payload);
                    return { sent: false, queued: false, reason: 'gtag_not_ready' };
                }

                gtag('event', payload.name, {
                    ...payload.params,
                    ...payload.options,
                });

                print('INFO', `Event sent from ${source}.`, payload);

                return { sent: true, queued: false, reason: null };
            };

            const dispatch = (rawPayload, source = 'manual') => {
                const payload = normalizeRawPayload(rawPayload);
                const result = validatePayload(payload);

                if (!result.valid) {
                    print('ERROR', `Invalid GA4 payload from ${source}.`, result.errors);

                    if (config.dropInvalidEvents === true) {
                        return { ok: false, sent: false, queued: false, errors: result.errors };
                    }
                }

                if (config.strictValidation === true && !result.valid) {
                    return { ok: false, sent: false, queued: false, errors: result.errors };
                }

                const transport = sendToGtag(result.payload, source);

                return {
                    ok: result.valid,
                    sent: transport.sent,
                    queued: transport.queued,
                    errors: result.errors,
                };
            };

            const track = (name, params = {}, options = {}) => {
                return dispatch({ name, params, options }, 'api');
            };

            window.addEventListener(config.eventBusName || 'ga4:event', (event) => {
                dispatch(event && event.detail ? event.detail : {}, 'dom');
            });

            document.addEventListener('livewire:init', () => {
                if (!window.Livewire || typeof window.Livewire.on !== 'function') {
                    print('WARN', 'Livewire is not available for event subscription.');
                    return;
                }

                window.Livewire.on(config.livewireEventName || 'ga4-event', (payload) => {
                    dispatch(normalizeRawPayload(payload), 'livewire');
                });

                print('INFO', 'Livewire listener registered.', config.livewireEventName || 'ga4-event');
            });

            window[config.globalJsObject || 'GA4Events'] = {
                track,
                dispatch,
                flushQueue,
                config,
            };

            print('INFO', 'GA4 bridge initialized.', config);

            if (!config.measurementId) {
                print('ERROR', 'Measurement ID is missing. Events might be dropped.');
            }

            setInterval(flushQueue, 600);
        })();
    </script>
@endif
