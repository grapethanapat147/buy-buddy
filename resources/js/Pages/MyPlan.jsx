import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };
const cadenceLabel = { weekly: 'รายสัปดาห์', monthly: 'รายเดือน' };

export default function MyPlan({ items, budget, total, overBudgetBy, mustExceedsBudget, storeRollup, restock }) {
    const { auth } = usePage().props;
    const [tab, setTab] = useState('list');
    const over = overBudgetBy > 0;

    return (
        <AppLayout>
            <h1 className="text-lg font-medium">แผนของฉัน</h1>

            <div className="mt-3 flex gap-4 border-b border-neutral-100">
                <button onClick={() => setTab('list')}
                    className={`pb-2 text-sm ${tab === 'list' ? 'border-b-2 border-neutral-800 font-medium' : 'text-neutral-500'}`}>รายการ</button>
                <button onClick={() => setTab('calendar')}
                    className={`pb-2 text-sm ${tab === 'calendar' ? 'border-b-2 border-neutral-800 font-medium' : 'text-neutral-500'}`}>ปฏิทิน</button>
            </div>

            {tab === 'list' && (
                <>
                    <div className="my-3"><BudgetMeter total={total} budget={budget} /></div>
                    {over && mustExceedsBudget && (
                        <div className="mb-3 rounded-lg bg-neutral-100 p-3 text-sm text-neutral-700">
                            ของจำเป็นล้วน ๆ ก็เกินงบ ฿{overBudgetBy.toLocaleString()} — เราไม่ตัดของจำเป็นให้ ลองเปลี่ยนรุ่นถูกกว่า หรือแบ่งซื้อข้ามเดือน
                        </div>
                    )}
                    {over && !mustExceedsBudget && (
                        <div className="mb-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                            เกินงบ ฿{overBudgetBy.toLocaleString()} — ลองเลื่อนของที่ไฮไลต์ไว้ไปซื้อรอบหน้า
                        </div>
                    )}
                    {!over && (
                        <div className="mb-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">
                            อยู่ในงบ · เหลือ ฿{(budget - total).toLocaleString()}
                        </div>
                    )}
                    <div className="divide-y divide-neutral-100">
                        {items.map((it) => (
                            <div key={it.productId} className={`flex items-center gap-3 py-3 ${it.suggested ? 'rounded-lg bg-amber-50 px-2' : ''}`}>
                                <div className="flex-1">
                                    <div className="text-sm">{it.name}</div>
                                    <div className="text-xs text-neutral-400">{tierLabel[it.tier]}</div>
                                </div>
                                <span className="text-sm text-neutral-500">฿{it.lineTotal.toLocaleString()}</span>
                                {it.tier !== 'must' && (
                                    <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                        className="rounded-lg border border-neutral-300 px-2 py-1 text-xs">เลื่อนออก</button>
                                )}
                            </div>
                        ))}
                    </div>
                    {items.length === 0 && <p className="py-6 text-center text-sm text-neutral-400">ยังไม่มีของในแผน</p>}
                    {storeRollup?.length > 0 && (
                        <div className="mt-4 rounded-lg bg-neutral-50 p-3 text-xs text-neutral-500">
                            ถ้าซื้อทั้งหมดที่: {storeRollup.map((s) => `${s.platform} ฿${s.total.toLocaleString()}`).join(' · ')}
                        </div>
                    )}
                </>
            )}

            {tab === 'calendar' && (
                <div className="mt-4">
                    {restock?.length > 0 ? restock.map((group) => (
                        <div key={group.cadence} className="mb-4">
                            <div className="mb-2 text-sm font-medium">{cadenceLabel[group.cadence] ?? group.cadence}</div>
                            <div className="divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                                {group.items.map((it) => (
                                    <div key={it.id} className="flex items-center justify-between p-3">
                                        <span className="text-sm">{it.name}</span>
                                        <span className="text-sm text-neutral-500">฿{it.price.toLocaleString()}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )) : <p className="py-6 text-center text-sm text-neutral-400">ยังไม่มีของสิ้นเปลืองในแผน — เพิ่มของหมวด Restock เพื่อวางแผนซื้อซ้ำ</p>}
                </div>
            )}

            <div className="mt-5 border-t border-neutral-100 pt-4">
                {auth?.user ? (
                    <button onClick={() => router.post('/plan/save', {}, { preserveScroll: true })}
                        className="w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">
                        เซฟแผนไว้ในบัญชี ({auth.user.name})
                    </button>
                ) : (
                    <Link href="/register" className="block rounded-lg bg-neutral-800 p-3 text-center font-medium text-white">
                        เซฟแผน (สมัคร/เข้าสู่ระบบ)
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}
