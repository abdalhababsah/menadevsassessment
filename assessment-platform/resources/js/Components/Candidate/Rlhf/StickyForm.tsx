import { PropsWithChildren } from 'react';

export function StickyForm({ children }: PropsWithChildren) {
    return (
        <div className="lg:sticky lg:top-24">
            {children}
        </div>
    );
}
