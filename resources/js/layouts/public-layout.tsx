import { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useFlashToast } from '@/hooks/use-flash-toast';

type PublicLayoutProps = {
    children: ReactNode;
    title?: string;
};

export default function PublicLayout({ children, title }: PublicLayoutProps) {
    useFlashToast();

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col items-center">
            <header className="w-full border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-2">
                        <AppLogoIcon className="h-6 w-6 text-primary fill-current" />
                        <span className="font-semibold text-gray-900 tracking-tight">InvoiceFlow</span>
                    </div>
                    {title && <span className="text-sm font-medium text-gray-500">{title}</span>}
                </div>
            </header>
            <main className="w-full max-w-5xl px-4 py-8 sm:px-6 flex-1">
                {children}
            </main>
            <footer className="w-full border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-400">
                Powered by InvoiceFlow
            </footer>
        </div>
    );
}
