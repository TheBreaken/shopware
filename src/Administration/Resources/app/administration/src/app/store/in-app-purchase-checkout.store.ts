/**
 * @package admin
 */
import type { iapCheckout } from '@shopware-ag/meteor-admin-sdk/es/iap';
import type { Extension } from 'src/app/state/extensions.store';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export type InAppPurchaseRequest = Omit<iapCheckout, 'responseType'>;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export type InAppPurchaseCheckoutState = {
    state: {
        entry: null,
        extension: null,
    } | {
        entry: InAppPurchaseRequest,
        extension: Extension,
    },
    actions: {
        request: (entry: InAppPurchaseRequest, extension: Extension) => void,
        dismiss: () => void,
    },
    getters: unknown,
}

Shopware.Store.register({
    id: 'inAppPurchaseCheckout',

    state: (): InAppPurchaseCheckoutState['state'] => ({
        entry: null,
        extension: null,
    }),

    actions: {
        request(entry: InAppPurchaseRequest, extension: Extension): void {
            this.entry = entry;
            this.extension = extension;
        },

        dismiss(): void {
            this.entry = null;
            this.extension = null;
        },
    },
});