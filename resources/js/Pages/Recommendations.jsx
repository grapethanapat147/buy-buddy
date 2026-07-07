import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierBadge = {
    must: 'bg-rose-50 text-rose-700',
    recommended: 'bg-amber-50 text-amber-700',
    optional: 'bg-ink/5 text-ink-soft',
};
const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };

export default function Recommendations({ items, budget, plannedTotal }) {
    return (
        <AppLayout>
            <h1 className="text-lg font-semibold text-ink">ชุดของแนะนำ</h1>
            <div className="my-3"><BudgetMeter total={plannedTotal} budget={budget} /></div>
            <div className="space-y-2">
                {items.map((it) => (
                    <div key={it.productId} className="flex items-center gap-3 rounded-xl border border-ink/8 bg-cream-card p-3 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift">
                        <div className="flex-1">
                            <Link href={`/products/${it.productId}`} className="text-sm font-semibold text-ink transition-colors hover:text-brand">{it.name}</Link>
                            <div className="mt-1 text-xs text-ink-soft tabular-nums">฿{it.lineTotal.toLocaleString()}</div>
                            <span className={`mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] ${tierBadge[it.tier]}`}>{tierLabel[it.tier]}</span>
                        </div>
                        {it.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                aria-label="เอาออกจากแผน"
                                className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">
                                <span className="animate-pop" aria-hidden="true">✓</span>
                            </button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${it.productId}`, {}, { preserveScroll: true })}
                                aria-label="เพิ่มลงแผน"
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-ink/15 text-ink-soft transition hover:bg-cream-sunk active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">+</button>
                        )}
                    </div>
                ))}
            </div>
            <Link href="/plan" className="mt-4 block rounded-full bg-brand p-3 text-center font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]">
                ดูแผนของฉัน
            </Link>
        </AppLayout>
    );
}
