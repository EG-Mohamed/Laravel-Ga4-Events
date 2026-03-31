@if ($ga4Config['enabled'])
    @if ($ga4Config['injectGtmScript'] && $ga4Config['containerId'])
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $ga4Config['containerId'] }}');</script>
    @endif

    <script id="gtm-events-config" type="application/json">{!! json_encode($ga4Config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <script>
        (() => {
            const readConfig = () => {
                const node = document.getElementById('gtm-events-config');
                if (!node) return {};
                try {
                    return JSON.parse(node.textContent || '{}');
                } catch (_) {
                    return {};
                }
            };

            const config = readConfig();
            window.dataLayer = window.dataLayer || [];
            let previousParamKeys = new Set();

            const print = (level, message, meta = null) => {
                if (!config.debug) return;
                const parts = [config.consolePrefix || '[GTM Events]', `[${level}]`, message];
                if (meta !== null) {
                    console.log(...parts, meta);
                    return;
                }
                console.log(...parts);
            };

            const sanitizeName = (value) => typeof value !== 'string' ? '' : value.trim();

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

                if (entries.length > maxParams) errors.push('Event params exceed max allowed count.');

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
                    if (typeof rawValue === 'string') {
                        values[key] = rawValue.slice(0, maxParamValueLength);
                        if (rawValue.length > maxParamValueLength) errors.push(`Event param value was truncated: ${key}`);
                        continue;
                    }
                    if (['number', 'boolean'].includes(typeof rawValue) || rawValue === null) {
                        values[key] = rawValue;
                        continue;
                    }
                    if (Array.isArray(rawValue) || (rawValue && typeof rawValue === 'object')) {
                        values[key] = rawValue; // السماح للمصفوفات بالمرور (مهم جداً للـ items)
                        continue;
                    }
                    errors.push(`Event param value type is not supported: ${key}`);
                }

                return { values, errors };
            };

            const normalizeRawPayload = (rawPayload) => {
                if (!rawPayload) return {};
                if (Array.isArray(rawPayload)) return (rawPayload.length === 1 && rawPayload[0] && typeof rawPayload[0] === 'object') ? rawPayload[0] : {};
                if (typeof rawPayload !== 'object') return {};
                if ('name' in rawPayload || 'params' in rawPayload) return rawPayload;
                if ('payload' in rawPayload && rawPayload.payload && typeof rawPayload.payload === 'object') return rawPayload.payload;
                if ('0' in rawPayload && rawPayload[0] && typeof rawPayload[0] === 'object') return rawPayload[0];
                return rawPayload;
            };

            const validatePayload = (payload) => {
                const errors = [];
                const name = sanitizeName(payload && payload.name ? payload.name : '');
                const maxEventNameLength = Number(config.maxEventNameLength || 40);
                const patternString = String(config.allowedNamePattern || '').replace(/^\//, '').replace(/\/[gimsuy]*$/, '');
                const pattern = new RegExp(patternString || '^[a-zA-Z][a-zA-Z0-9_]*$');

                if (!name.length) errors.push('Event name is required.');
                if (name.length > maxEventNameLength) errors.push('Event name exceeds max allowed length.');
                if (name.length && !pattern.test(name)) errors.push('Event name does not match allowed pattern.');

                const payloadParams = payload && payload.params ? payload.params : {};
                const { values: params, errors: paramErrors } = sanitizeParams(payloadParams);
                errors.push(...paramErrors);

                return { valid: errors.length === 0, errors, payload: { name, params } };
            };

            const pushToDataLayer = (payload, source) => {
                const nextParams = payload && payload.params && typeof payload.params === 'object' ? payload.params : {};
                const flushParams = {};

                previousParamKeys.forEach((key) => {
                    if (!(key in nextParams)) flushParams[key] = undefined;
                });

                // قائمة بأحداث الـ Ecommerce القياسية في GA4
                const ecommerceEvents = [
                    'view_item', 'view_item_list', 'select_item', 'add_to_cart',
                    'remove_from_cart', 'view_cart', 'begin_checkout',
                    'add_shipping_info', 'add_payment_info', 'purchase', 'refund'
                ];

                const isEcommerce = ecommerceEvents.includes(payload.name);

                let pushPayload = {
                    event: payload.name,
                    ...flushParams,
                };

                if (isEcommerce) {
                    // تنظيف الـ Data Layer من أي أحداث Ecommerce سابقة (توصية رسمية من جوجل)
                    window.dataLayer.push({ ecommerce: null });

                    pushPayload.ecommerce = { ...nextParams };

                    // تصحيح تلقائي في حال تم إرسال item بدلاً من items من الباك إند
                    if (pushPayload.ecommerce.item && !pushPayload.ecommerce.items) {
                        pushPayload.ecommerce.items = Array.isArray(pushPayload.ecommerce.item)
                            ? pushPayload.ecommerce.item
                            : [pushPayload.ecommerce.item];
                        delete pushPayload.ecommerce.item;
                    }
                } else {
                    pushPayload.ecommerce = null; // للأحداث العادية غير التجارية
                    Object.assign(pushPayload, nextParams);
                }

                window.dataLayer.push(pushPayload);
                previousParamKeys = new Set(Object.keys(nextParams));

                print('INFO', `Event pushed to dataLayer from ${source}.`, pushPayload);
                return { sent: true };
            };

            const dispatch = (rawPayload, source = 'manual') => {
                const payload = normalizeRawPayload(rawPayload);
                const result = validatePayload(payload);

                if (!result.valid) {
                    print('ERROR', `Invalid GTM payload from ${source}.`, result.errors);
                    if (config.dropInvalidEvents === true) return { ok: false, sent: false, errors: result.errors };
                }

                if (config.strictValidation === true && !result.valid) return { ok: false, sent: false, errors: result.errors };

                pushToDataLayer(result.payload, source);
                return { ok: result.valid, sent: true, errors: result.errors };
            };

            const track = (name, params = {}) => dispatch({ name, params }, 'api');

            window.addEventListener(config.eventBusName || 'gtm:event', (event) => {
                dispatch(event && event.detail ? event.detail : {}, 'dom');
            });

            document.addEventListener('livewire:init', () => {
                if (!window.Livewire || typeof window.Livewire.on !== 'function') {
                    print('WARN', 'Livewire is not available for event subscription.');
                    return;
                }

                window.Livewire.on(config.livewireEventName || 'gtm-event', (payload) => {
                    dispatch(normalizeRawPayload(payload), 'livewire');
                });

                print('INFO', 'Livewire listener registered.', config.livewireEventName || 'gtm-event');
            });

            window[config.globalJsObject || 'GTMEvents'] = { track, dispatch, config };
            print('INFO', 'GTM bridge initialized.', config);

            if (!config.containerId) print('WARN', 'Container ID is missing. Auto GTM script injection is disabled.');
        })();
    </script>
@endif