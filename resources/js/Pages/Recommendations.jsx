import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierBadge = {
    must: 'bg-rose-50 text-rose-700',
    recommended: 'bg-amber-50 text-amber-700',
    optional: 'bg-neutral-100 text-neutral-600',
};
const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };

export default function Recommendations({ items, budget, plannedTotal }) {
    return (
        <AppLayout>
            <h1 className="text-lg font-medium">ชุดของแนะนำ</h1>
            <div className="my-3"><BudgetMeter total={plannedTotal} budget={budget} /></div>
            <div className="space-y-2">
                {items.map((it) => (
                    <div key={it.productId} className="flex items-center gap-3 rounded-xl border border-neutral-200 p-3">
                        <div className="flex-1">
                            <Link href={`/products/${it.productId}`} className="text-sm font-medium">{it.name}</Link>
                            <div className="mt-1 text-xs text-neutral-500">฿{it.lineTotal.toLocaleString()}</div>
                            <span className={`mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] ${tierBadge[it.tier]}`}>{tierLabel[it.tier]}</span>
                        </div>
                        {it.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full bg-emerald-50 text-emerald-600">✓</button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${it.productId}`, {}, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full border border-neutral-300">+</button>
                        )}
                    </div>
                ))}
            </div>
            <Link href="/plan" className="mt-4 block rounded-lg bg-neutral-800 p-3 text-center font-medium text-white">
                ดูแผนของฉัน
            </Link>
        </AppLayout>
    );
}
