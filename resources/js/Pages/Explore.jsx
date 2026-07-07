import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Explore({ categories, activeCategory, query, products }) {
    const go = (params) => router.get('/explore', params, { preserveState: true, preserveScroll: true });

    return (
        <AppLayout>
            <h1 className="text-lg font-medium">เลือกดูของเอง</h1>
            <input
                defaultValue={query}
                placeholder="ค้นหาสินค้า"
                onKeyDown={(e) => { if (e.key === 'Enter') go({ q: e.target.value, category: activeCategory }); }}
                className="mt-3 w-full rounded-lg border border-neutral-200 p-2"
            />
            <div className="mt-3 flex flex-wrap gap-2">
                <button onClick={() => go({ q: query })}
                    className={`rounded-full px-3 py-1 text-xs ${activeCategory === '' ? 'bg-sky-50 text-sky-700' : 'border border-neutral-200 text-neutral-600'}`}>ทั้งหมด</button>
                {categories.map((c) => (
                    <button key={c.slug} onClick={() => go({ category: c.slug, q: query })}
                        className={`rounded-full px-3 py-1 text-xs ${activeCategory === c.slug ? 'bg-sky-50 text-sky-700' : 'border border-neutral-200 text-neutral-600'}`}>{c.name}</button>
                ))}
            </div>
            <div className="mt-4 space-y-2">
                {products.map((p) => (
                    <div key={p.id} className="flex items-center gap-3 rounded-xl border border-neutral-200 p-3">
                        <div className="flex-1">
                            <Link href={`/products/${p.id}`} className="text-sm font-medium">{p.name}</Link>
                            <div className="text-xs text-neutral-500">{p.category} · ฿{p.price.toLocaleString()}</div>
                        </div>
                        {p.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${p.id}`, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full bg-emerald-50 text-emerald-600">✓</button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${p.id}`, {}, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full border border-neutral-300">+</button>
                        )}
                    </div>
                ))}
                {products.length === 0 && <p className="py-6 text-center text-sm text-neutral-400">ไม่พบสินค้า</p>}
            </div>
        </AppLayout>
    );
}
