document.addEventListener('youla:init', () => {
    /**
     * WebAuthn ceremonies for server-built options: `$passkey.get(options)` signs in,
     * `$passkey.create(options)` registers, `$passkey.autofill(() => $ajax.post(...))` offers passkeys
     * in the login field (`autocomplete="username webauthn"`). Each resolves the credential as JSON for
     * the API, or null when the prompt was cancelled or failed (the failure goes to `$notice`).
     * A new ceremony aborts the pending one: browsers allow only one at a time.
     *
     * `$passkey.available` tells whether the device can use passkeys, for `hidden u-show="$passkey.available"`.
     * The device check is asynchronous, so its last result is kept and used right away on the next pages.
     * Network calls stay in the markup:
     *
     * `$ajax.post('user/passkey-options').then(({options}) => $passkey.get(options)).then(credential => credential && $ajax.post('user/passkey-sign-in', {credential}))`
     *
     * @since 2026.1
     */
    (() => {
        const STORAGE_KEY = 'expansa-passkeys';
        const supported = typeof window.PublicKeyCredential === 'function' && !!navigator.credentials;

        const storage = (value = null) => {
            try {
                return value === null ? localStorage.getItem(STORAGE_KEY) : localStorage.setItem(STORAGE_KEY, value);
            } catch {
                return null;
            }
        };

        // a built-in authenticator (fingerprint, face, screen lock) or a phone reachable by QR code
        const detect = async () => {
            const capabilities = await PublicKeyCredential.getClientCapabilities?.().catch(() => null);
            if (capabilities?.passkeyPlatformAuthenticator || capabilities?.userVerifyingPlatformAuthenticator || capabilities?.hybridTransport) {
                return true;
            }

            return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(() => false);
        };

        let pending = null;

        const decode = value => {
            const base64 = value.replace(/-/g, '+').replace(/_/g, '/');

            return Uint8Array.from(atob(base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=')), char => char.charCodeAt(0));
        };

        const encode = buffer => {
            let binary = '';
            new Uint8Array(buffer).forEach(byte => binary += String.fromCharCode(byte));

            return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        };

        const withBinaryIds = (credentials = []) => credentials.map(credential => ({...credential, id: decode(credential.id)}));

        // fallbacks for browsers without PublicKeyCredential.parse*OptionsFromJSON() and toJSON()
        const parse = {
            get: options => PublicKeyCredential.parseRequestOptionsFromJSON?.(options) ?? {
                ...options,
                challenge: decode(options.challenge),
                allowCredentials: withBinaryIds(options.allowCredentials),
            },
            create: options => PublicKeyCredential.parseCreationOptionsFromJSON?.(options) ?? {
                ...options,
                challenge: decode(options.challenge),
                user: {...options.user, id: decode(options.user.id)},
                excludeCredentials: withBinaryIds(options.excludeCredentials),
            },
        };

        const serialize = credential => {
            if (typeof credential.toJSON === 'function') {
                return credential.toJSON();
            }

            const {response} = credential;

            const json = {
                id: credential.id,
                rawId: encode(credential.rawId),
                type: credential.type,
                response: {clientDataJSON: encode(response.clientDataJSON)},
                clientExtensionResults: credential.getClientExtensionResults(),
            };

            if (response.attestationObject) {
                json.response.attestationObject = encode(response.attestationObject);
                json.response.transports = response.getTransports?.() ?? [];
            } else {
                json.response.authenticatorData = encode(response.authenticatorData);
                json.response.signature = encode(response.signature);
                json.response.userHandle = response.userHandle ? encode(response.userHandle) : null;
            }

            return json;
        };

        const ceremony = async (kind, options, mediation) => {
            // no options: the API answered with notice fragments instead
            if (!supported || !options) {
                return null;
            }

            pending?.abort();

            const controller = pending = new AbortController();

            try {
                const credential = await navigator.credentials[kind]({
                    publicKey: parse[kind](options),
                    signal: controller.signal,
                    ...(mediation && {mediation}),
                });

                return JSON.stringify(serialize(credential));
            } catch (error) {
                // NotAllowedError: the user closed the prompt or it timed out; AbortError: another ceremony started
                if (error.name !== 'NotAllowedError' && error.name !== 'AbortError') {
                    document.querySelector('[u-data="notice"]')?.__x?.data?.error(error.message);
                }

                return null;
            } finally {
                if (pending === controller) {
                    pending = null;
                }
            }
        };

        const passkey = {
            supported,
            available: supported && storage() !== '0',
            get: options => ceremony('get', options),
            create: options => ceremony('create', options),
            // takes a request for the options, so they are fetched only where autofill works
            autofill: async request => supported && await PublicKeyCredential.isConditionalMediationAvailable?.().catch(() => false)
                ? ceremony('get', (await request())?.options, 'conditional')
                : null,
            // label for a new passkey: the platform the browser runs on
            device: () => navigator.userAgentData?.platform || navigator.platform || '',
        };

        Youla.variable('passkey', () => passkey);

        if (supported) {
            detect().then(available => {
                storage(available ? '1' : '0');

                if (passkey.available !== available) {
                    passkey.available = available;
                    document.querySelectorAll('[u-data]').forEach(root => Youla.forceRefresh(root));
                }
            });
        }
    })();
});
