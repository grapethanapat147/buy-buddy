import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import IconTile from '@/Components/IconTile';

export default function Explore({ categories, activeCategory, query, products }) {
    const go = (params) => router.get('/explore', params, { preserveState: true, preserveScroll: true });

    const chip = (active) =>
        `rounded-full px-3 py-1 text-xs transition active:scale-95 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none ${
            active ? 'bg-brand-50 font-semibold text-brand-700' : 'border border-ink/10 text-ink-soft hover:bg-cream-sunk'
        }`;

    return (
        <AppLayout>
            <h1 className="text-2xl font-semibold text-ink">เลือกดูของเอง</h1>
            <input
                defaultValue={query}
                placeholder="ค้นหาสินค้า"
                onKeyDown={(e) => { if (e.key === 'Enter') go({ q: e.target.value, category: activeCategory }); }}
                className="mt-3 w-full rounded-xl border border-ink/10 bg-cream-card px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
            />
            <div className="mt-3 flex flex-wrap gap-2">
                <button onClick={() => go({ q: query })} className={chip(activeCategory === '')}>ทั้งหมด</button>
                {categories.map((c) => (
                    <button key={c.slug} onClick={() => go({ category: c.slug, q: query })} className={chip(activeCategory === c.slug)}>{c.name}</button>
                ))}
            </div>
            <div className="mt-4 space-y-2">
                {products.map((p) => (
                    <div key={p.id} className="flex items-center gap-3 rounded-xl border border-ink/8 bg-cream-card p-3 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift">
                        <IconTile icon={p.icon} />
                        <div className="min-w-0 flex-1">
                            <Link href={`/products/${p.id}`} className="text-sm font-semibold text-ink transition-colors hover:text-brand">{p.name}</Link>
                            <div className="text-xs text-ink-soft tabular-nums">{p.category} · ฿{p.price.toLocaleString()}</div>
                        </div>
                        {p.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${p.id}`, { preserveScroll: true })}
                                aria-label="เอาออกจากแผน"
                                className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">
                                <span className="animate-pop" aria-hidden="true">✓</span>
                            </button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${p.id}`, {}, { preserveScroll: true })}
                                aria-label="เพิ่มลงแผน"
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-ink/15 text-ink-soft transition hover:bg-cream-sunk active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">+</button>
                        )}
                    </div>
                ))}
                {products.length === 0 && <p className="py-6 text-center text-sm text-ink-muted">ไม่พบสินค้า</p>}
            </div>
        </AppLayout>
    );
}
