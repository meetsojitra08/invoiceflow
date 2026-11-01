import type { Auth, FlashMessages } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: FlashMessages;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
