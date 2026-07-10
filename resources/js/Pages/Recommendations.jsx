import { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'motion/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';
import ReadinessMeter from '@/Components/ReadinessMeter';
import IconTile from '@/Components/IconTile';
import Mascot from '@/Components/Mascot';
import { celebrate } from '@/lib/celebrate';

const tierBadge = {
    must: 'bg-rose-50 text-rose-700',
    recommended: 'bg-amber-50 text-amber-700',
    optional: 'bg-ink/5 text-ink-soft',
};
const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };

export default function Recommendations({ categories, budget, plannedTotal, readiness }) {
    const [toast, setToast] = useState(null);
    const prev = useRef(null);

    useEffect(() => {
        const doneNow = {};
        categories.forEach((c) => {
            doneNow[c.name] = c.total > 0 && c.collected === c.total;
        });

        if (prev.current) {
            const justReady = readiness.percent >= 100 && prev.current.percent < 100;
            const newlyDone = categories.find((c) => doneNow[c.name] && !prev.current.done[c.name]);

            if (justReady) {
                celebrate();
                setToast('ห้องพร้อมอยู่แล้ว! เยี่ยมไปเลย 🎉');
            } else if (newlyDone) {
                celebrate();
                setToast(`${newlyDone.name}ครบแล้ว! 🎉`);
            }
        }

        prev.current = { done: doneNow, percent: readiness.percent };
    }, [categories, readiness.percent]);

    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => setToast(null), 2400);
        return () => clearTimeout(t);
    }, [toast]);

    return (
        <AppLayout>
            <h1 className="text-2xl font-semibold text-ink">จัดห้องกันเลย</h1>
            <p className="mt-1 text-sm text-ink-soft">เก็บของจำเป็นให้ครบ แล้วห้องก็พร้อมอยู่</p>

            <div className="mt-3 space-y-2">
                <ReadinessMeter percent={readiness.percent} />
                <div className="rounded-2xl bg-cream-card p-4 shadow-soft">
                    <div className="mb-1.5 text-sm font-medium text-ink-soft">💰 งบ</div>
                    <BudgetMeter total={plannedTotal} budget={budget} />
                </div>
            </div>

            <div className="mt-4 space-y-5">
                {categories.map((cat) => {
                    const done = cat.collected === cat.total && cat.total > 0;
                    return (
                        <section key={cat.name}>
                            <div className="mb-2 flex items-center justify-between">
                                <h2 className="text-base font-semibold text-ink">{cat.name}</h2>
                                {done ? (
                                    <span className="animate-pop rounded-full bg-brand px-2.5 py-0.5 text-xs font-semibold text-white">✓ ครบ!</span>
                                ) : (
                                    <span className="rounded-full bg-cream-sunk px-2.5 py-0.5 text-xs font-semibold text-ink-soft tabular-nums">{cat.collected}/{cat.total}</span>
                                )}
                            </div>
                            <div className="space-y-2">
                                {cat.items.map((it) => (
                                    <div key={it.productId} className="flex items-center gap-3 rounded-xl border border-ink/8 bg-cream-card p-3 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift">
                                        <IconTile icon={it.icon} />
                                        <div className="min-w-0 flex-1">
                                            <Link href={`/products/${it.productId}`} className="text-sm font-semibold text-ink transition-colors hover:text-brand">{it.name}</Link>
                                            <div className="mt-1 text-xs text-ink-soft tabular-nums">฿{it.lineTotal.toLocaleString()}</div>
                                            <span className={`mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] ${tierBadge[it.tier]}`}>{tierLabel[it.tier]}</span>
                                        </div>
                                        {it.inPlan ? (
                                            <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                                aria-label="เอาออกจากกระเป๋า"
                                                className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">
                                                <span className="animate-pop" aria-hidden="true">✓</span>
                                            </button>
                                        ) : (
                                            <button onClick={() => router.post(`/plan/items/${it.productId}`, {}, { preserveScroll: true })}
                                                aria-label="เก็บลงกระเป๋า"
                                                className="flex h-9 w-9 items-center justify-center rounded-full border border-ink/15 text-ink-soft transition hover:bg-cream-sunk active:scale-90 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">+</button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </section>
                    );
                })}
                {categories.length === 0 && <p className="py-8 text-center text-sm text-ink-muted">ยังไม่มีของ เริ่มเก็บกันเลย!</p>}
            </div>

            <Link href="/plan" className="mt-6 block rounded-full bg-brand p-4 text-center text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]">
                ดูแผนของฉัน
            </Link>

            <AnimatePresence>
                {toast && (
                    <motion.div
                        key="celebrate-toast"
                        initial={{ opacity: 0, y: 16, scale: 0.96 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 16, scale: 0.96 }}
                        transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
                        className="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4"
                    >
                        <div className="flex items-center gap-2 rounded-full bg-ink px-4 py-2.5 text-sm font-semibold text-cream shadow-lift">
                            <Mascot mood="celebrate" className="text-lg" />
                            <span>{toast}</span>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </AppLayout>
    );
}
