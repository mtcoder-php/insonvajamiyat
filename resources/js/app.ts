import { createInertiaApp, router } from '@inertiajs/vue3';
import {
    applyThemeForComponent,
    initializeTheme,
} from '@/composables/useAppearance';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import WebLayout from '@/layouts/WebLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { initialPageLocale, loadLocale } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Tanlangan til lug'ati (ru/en) birinchi chizishdan oldin yuklanadi — o'zbekcha matn "miltillamaydi"
void loadLocale(initialPageLocale()).finally(() => {
    void createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        // Sahifa papkasi → layout. Tizim uch qismga ajratilgan:
        //   web/*      — ommaviy sayt (TZ 4.1.1)
        //   cabinet/*  — muallif kabineti (TZ 4.1.3)
        //   admin/*    — admin panel (TZ 4.2)
        layout: (name) => {
            switch (true) {
                case name.startsWith('web/'):
                    return WebLayout;
                case name.startsWith('cabinet/'):
                    return CabinetLayout;
                case name.startsWith('admin/'):
                    return AdminLayout;
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('settings/'):
                    return [AppLayout, SettingsLayout];
                // Xato sahifalari (404, 500…) — mustaqil, layout'siz
                case name.startsWith('errors/'):
                    return undefined;
                default:
                    return AppLayout;
            }
        },
        withApp: (app) => {
            app.directive('focus', {
                mounted: (el: HTMLElement, shouldFocus) => {
                    if (shouldFocus.value !== false) {
                        el.focus();
                    }
                },
            });
        },
        progress: {
            color: '#006CF6',
        },
    });
});

// This will set light / dark mode on page load...
initializeTheme();

// Web/auth/kabinet sahifalari — faqat yorug' mavzu; admin panelga o'tganda foydalanuvchi tanlovi qaytadi
router.on('navigate', (event) => {
    const { component, props } = event.detail.page;

    applyThemeForComponent(component, props.auth?.isStaff === true);
    // Til almashtirilganda (yoki kirishda profil tili qo'llanganda) lug'at yuklanadi
    void loadLocale(props.locale);
});

// This will listen for flash toast data from the server...
initializeFlashToast();
