import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'motion/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const EASE = [0.22, 1, 0.36, 1];
const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };
const cadenceLabel = { weekly: 'รายสัปดาห์', monthly: 'รายเดือน' };

function Tab({ active, onClick, children }) {
    return (
        <button
            onClick={onClick}
            className={`-mb-px pb-2 text-sm transition-colors ${
                active ? 'border-b-2 border-brand font-semibold text-ink' : 'text-ink-soft hover:text-ink'
            }`}
        >
            {children}
        </button>
    );
}

export default function MyPlan({ items, budget, total, overBudgetBy, mustExceedsBudget, storeRollup, restock }) {
    const { auth } = usePage().props;
    const [tab, setTab] = useState('list');
    const over = overBudgetBy > 0;

    return (
        <AppLayout>
            <h1 className="text-2xl font-semibold text-ink">แผนของฉัน</h1>

            <div className="mt-3 flex gap-5 border-b border-ink/10">
                <Tab active={tab === 'list'} onClick={() => setTab('list')}>รายการ</Tab>
                <Tab active={tab === 'calendar'} onClick={() => setTab('calendar')}>ปฏิทิน</Tab>
            </div>

            {tab === 'list' && (
                <>
                    <div className="my-4"><BudgetMeter total={total} budget={budget} /></div>

                    <AnimatePresence mode="wait" initial={false}>
                        {over && mustExceedsBudget && (
                            <motion.div key="must" initial={{ opacity: 0, y: -4 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}
                                className="mb-3 flex gap-2 rounded-xl bg-cream-sunk p-3 text-sm text-ink-soft">
                                <span aria-hidden="true">🤝</span>
                                <span>ของจำเป็นล้วน ๆ ก็เกินงบ ฿{overBudgetBy.toLocaleString()} — เราไม่ตัดของจำเป็นให้ ลองเปลี่ยนรุ่นถูกกว่า หรือแบ่งซื้อข้ามเดือน</span>
                            </motion.div>
                        )}
                        {over && !mustExceedsBudget && (
                            <motion.div key="defer" initial={{ opacity: 0, y: -4 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}
                                className="mb-3 flex gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                                <span aria-hidden="true">💡</span>
                                <span>เกินงบ ฿{overBudgetBy.toLocaleString()} — ลองเลื่อนของที่ไฮไลต์ไว้ไปซื้อรอบหน้า</span>
                            </motion.div>
                        )}
                        {!over && (
                            <motion.div key="ok" initial={{ opacity: 0, y: -4 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}
                                className="mb-3 flex gap-2 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">
                                <span aria-hidden="true">✓</span>
                                <span>อยู่ในงบ · เหลือ ฿{(budget - total).toLocaleString()}</span>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    <div>
                        <AnimatePresence initial={false}>
                            {items.map((it) => (
                                <motion.div
                                    key={it.productId}
                                    layout
                                    initial={{ opacity: 0, y: 6 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, x: 24 }}
                                    transition={{ duration: 0.22, ease: EASE }}
                                    className={`flex items-center gap-3 border-b border-ink/5 py-3 ${it.suggested ? 'rounded-xl bg-amber-50 px-3' : ''}`}
                                >
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate text-sm text-ink">{it.name}</div>
                                        <div className="text-xs text-ink-muted">{tierLabel[it.tier]}</div>
                                    </div>
                                    <span className="text-sm text-ink-soft tabular-nums">฿{it.lineTotal.toLocaleString()}</span>
                                    {it.tier !== 'must' && (
                                        <button
                                            onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                            className="rounded-full border border-ink/15 px-3 py-1 text-xs text-ink-soft transition hover:bg-cream-sunk active:scale-95"
                                        >
                                            เลื่อนออก
                                        </button>
                                    )}
                                </motion.div>
                            ))}
                        </AnimatePresence>
                    </div>

                    {items.length === 0 && <p className="py-8 text-center text-sm text-ink-muted">ยังไม่มีของในแผน</p>}

                    {storeRollup?.length > 0 && (
                        <div className="mt-4 rounded-xl bg-cream-sunk p-3 text-xs text-ink-soft">
                            ถ้าซื้อทั้งหมดที่: {storeRollup.map((s) => `${s.platform} ฿${s.total.toLocaleString()}`).join(' · ')}
                        </div>
                    )}
                </>
            )}

            {tab === 'calendar' && (
                <div className="mt-4">
                    {restock?.length > 0 ? restock.map((group) => (
                        <div key={group.cadence} className="mb-4">
                            <div className="mb-2 text-sm font-semibold text-ink">{cadenceLabel[group.cadence] ?? group.cadence}</div>
                            <div className="divide-y divide-ink/5 rounded-xl border border-ink/8">
                                {group.items.map((it) => (
                                    <div key={it.id} className="flex items-center justify-between p-3">
                                        <span className="text-sm text-ink">{it.name}</span>
                                        <span className="text-sm text-ink-soft tabular-nums">฿{it.price.toLocaleString()}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )) : <p className="py-8 text-center text-sm text-ink-muted">ยังไม่มีของสิ้นเปลืองในแผน — เพิ่มของหมวด Restock เพื่อวางแผนซื้อซ้ำ</p>}
                </div>
            )}

            <div className="mt-6 border-t border-ink/10 pt-4">
                {auth?.user ? (
                    <button
                        onClick={() => router.post('/plan/save', {}, { preserveScroll: true })}
                        className="w-full rounded-full bg-brand p-4 text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]"
                    >
                        เซฟแผนไว้ในบัญชี ({auth.user.name})
                    </button>
                ) : (
                    <Link
                        href="/register"
                        className="block rounded-full bg-brand p-4 text-center text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]"
                    >
                        เซฟแผน (สมัคร/เข้าสู่ระบบ)
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}
