<?php

namespace App\Support;

/**
 * Daftar file CSS tema terang per view (public/css/light/*.css).
 * File ini dibuat otomatis oleh installer tema - jangan diedit manual.
 */
class ThemeAssets
{
    public const VERSION = '4d1033fdf1';

    public const VIEWS = [
        'admin.bookings.reschedule' => 'admin.bookings.reschedule.css',
        'admin.customers.index' => 'admin.customers.index.css',
        'admin.customers.show' => 'admin.customers.show.css',
        'admin.dashboard' => 'admin.dashboard.css',
        'admin.layouts.panel' => 'admin.layouts.panel.css',
        'admin.offline-booking' => 'admin.offline-booking.css',
        'admin.payments.receipt' => 'admin.payments.receipt.css',
        'admin.schedule-blocks.index' => 'admin.schedule-blocks.index.css',
        'admin.settings.index' => 'admin.settings.index.css',
        'auth.forgot-password' => 'auth.forgot-password.css',
        'auth.login' => 'auth.login.css',
        'auth.register' => 'auth.register.css',
        'auth.reset-password' => 'auth.reset-password.css',
        'auth.verify-email' => 'auth.verify-email.css',
        'booking' => 'booking.css',
        'contact' => 'contact.css',
        'dashboard' => 'dashboard.css',
        'event' => 'event.css',
        'galeri' => 'galeri.css',
        'layouts.auth.simple' => 'layouts.auth.simple.css',
        'maintenance' => 'maintenance.css',
        'notifications._styles' => 'notifications._styles.css',
        'notifications.customer-layout' => 'notifications.customer-layout.css',
        'pages.auth.login' => 'pages.auth.login.css',
        'partials.lesson-pricing' => 'partials.lesson-pricing.css',
        'partials.mobile-tabbar' => 'partials.mobile-tabbar.css',
        'partials.notification-bell' => 'partials.notification-bell.css',
        'partials.site-navbar' => 'partials.site-navbar.css',
        'payment' => 'payment.css',
        'payments._booking-status' => 'payments._booking-status.css',
        'payments.booking' => 'payments.booking.css',
        'program' => 'program.css',
    ];
}
