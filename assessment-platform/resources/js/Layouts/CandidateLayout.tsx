import { PropsWithChildren } from 'react';
import { Toaster } from '@/components/ui/sonner';
import FlashNotifications from '@/components/FlashNotifications';

export default function CandidateLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gray-50">
            <header className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center px-4">
                    <h1 className="text-base font-semibold text-gray-900">Assessment Platform</h1>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-10">
                {children}
            </main>
            <footer className="border-t border-gray-200 bg-white py-6">
                <p className="mx-auto max-w-5xl px-4 text-center text-xs text-gray-500">
                    Powered by the Assessment Platform
                </p>
            </footer>
            <Toaster position="bottom-right" theme="light" />
            <FlashNotifications />
        </div>
    );
}
