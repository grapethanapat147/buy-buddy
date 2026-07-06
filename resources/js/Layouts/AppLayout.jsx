import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { planCount } = usePage().props;
    return (
        <div className="mx-auto max-w-xl p-4">
            <header className="mb-4 flex items-center justify-between">
                <Link href="/" className="font-medium">Grocery List</Link>
                <Link href="/plan" className="text-sm text-neutral-600">กระเป๋า ({planCount ?? 0})</Link>
            </header>
            <main className="rounded-xl border border-neutral-200 bg-white p-4">{children}</main>
        </div>
    );
}
