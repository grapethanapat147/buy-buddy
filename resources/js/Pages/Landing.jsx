import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const perks = [['🎯', 'แนะนำตามคุณ'], ['💸', 'คุ้มทุกบาท'], ['📅', 'วางแผนซื้อซ้ำ']];

export default function Landing() {
    return (
        <AppLayout>
            <div className="py-6 text-center">
                <div className="mx-auto mb-5 flex h-28 w-28 items-center justify-center rounded-full bg-brand-50 text-6xl" aria-hidden="true">🛍️</div>
                <h1 className="text-3xl font-bold leading-tight text-ink">จัดของเข้าห้อง<br />ง่าย ๆ ตามงบ</h1>
                <p className="mx-auto mt-3 max-w-sm text-lg text-ink-soft">BuyBuddy ช่วยแนะนำว่าต้องซื้ออะไร คุ้มสุดที่ร้านไหน และวางแผนไม่ให้เกินงบ</p>
                <div className="mt-8 space-y-3">
                    <Link href="/wizard" className="block rounded-full bg-brand p-4 text-center text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]">
                        เริ่มเลย — ตอบ 4 ข้อ
                    </Link>
                    <Link href="/explore" className="block rounded-full border border-ink/15 p-4 text-center text-lg font-semibold text-ink transition hover:bg-cream-sunk active:scale-95">
                        เลือกดูของเอง
                    </Link>
                </div>
                <div className="mt-10 grid grid-cols-3 gap-3">
                    {perks.map(([emoji, label]) => (
                        <div key={label} className="rounded-2xl bg-cream-sunk p-4">
                            <div className="text-3xl" aria-hidden="true">{emoji}</div>
                            <div className="mt-2 text-sm font-medium text-ink-soft">{label}</div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
