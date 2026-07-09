import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ProductDetail({ product, bundle }) {
    const bundleTotal = bundle.reduce((s, b) => s + b.price, 0);
    return (
        <AppLayout>
            <h1 className="text-2xl font-semibold text-ink">{product.name}</h1>
            <div className="mt-3 rounded-xl border border-ink/10 bg-cream-card shadow-soft">
                <div className="flex items-center justify-between p-3">
                    <span className="text-sm text-ink-soft">เทียบราคา</span>
                    <span className="text-xs text-ink-muted">ราคาอ้างอิง</span>
                </div>
                <div className="flex items-center justify-between border-t border-ink/8 bg-emerald-50 p-3 text-emerald-700">
                    <span className="text-sm font-semibold">{product.cheapest.platform ?? 'ราคาอ้างอิง'} · คุ้มสุด</span>
                    <span className="font-semibold tabular-nums">฿{product.cheapest.price.toLocaleString()}</span>
                </div>
                {product.otherStoreCount > 0 && (
                    <div className="border-t border-ink/8 p-2 text-center text-sm text-ink-soft">
                        ดูอีก {product.otherStoreCount} ร้าน
                    </div>
                )}
            </div>

            {bundle.length > 0 && (
                <>
                    <p className="mt-4 text-sm font-semibold text-ink">มักซื้อคู่กับ</p>
                    <div className="mt-2 rounded-xl border border-ink/8 bg-cream-card shadow-soft">
                        {bundle.map((b) => (
                            <div key={b.id} className="flex items-center justify-between border-b border-ink/5 p-3 last:border-0">
                                <span className="text-sm text-ink">{b.name}</span>
                                <span className="text-sm text-ink-soft tabular-nums">฿{b.price.toLocaleString()}</span>
                            </div>
                        ))}
                        <div className="flex items-center justify-between bg-cream-sunk p-3">
                            <span className="text-sm text-ink-soft tabular-nums">ทั้งชุด · ฿{bundleTotal.toLocaleString()}</span>
                            <button onClick={() => bundle.forEach((b) => router.post(`/plan/items/${b.id}`, {}, { preserveScroll: true }))}
                                className="rounded-full border border-ink/15 px-3 py-1.5 text-sm text-ink transition hover:bg-cream-card active:scale-95 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">ใส่ทั้งชุด</button>
                        </div>
                    </div>
                </>
            )}

            <button onClick={() => router.post(`/plan/items/${product.id}`, {}, { preserveScroll: true })}
                className="mt-4 w-full rounded-full bg-brand p-4 text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98] focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none">+ ใส่ลงแผน</button>
        </AppLayout>
    );
}
