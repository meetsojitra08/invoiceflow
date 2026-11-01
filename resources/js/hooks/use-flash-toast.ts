import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { FlashMessages } from '@/types/auth';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    const { flash } = usePage<{ flash?: FlashMessages }>().props;
    const lastFlash = useRef<{ success?: string | null; error?: string | null }>({});

    useEffect(() => {
        if (flash?.success && flash.success !== lastFlash.current.success) {
            toast.success(flash.success);
            lastFlash.current.success = flash.success;
        }
        if (flash?.error && flash.error !== lastFlash.current.error) {
            toast.error(flash.error);
            lastFlash.current.error = flash.error;
        }
    }, [flash]);

    useEffect(() => {
        return router.on('flash', (event) => {
            const f = (event as CustomEvent).detail?.flash;
            const data = f?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });
    }, []);
}
