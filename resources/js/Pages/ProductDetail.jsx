import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ProductDetail({ product, bundle }) {
    const bundleTotal = bundle.reduce((s, b) => s + b.price, 0);
    return (
        <AppLayout>
            <h1 className="text-lg font-medium">{product.name}</h1>
            <div className="mt-3 rounded-xl border border-neutral-300">
                <div className="flex items-center justify-between p-3">
                    <span className="text-sm text-neutral-500">เทียบราคา</span>
                    <span className="text-xs text-neutral-400">ราคาอ้างอิง</span>
                </div>
                <div className="flex items-center justify-between border-t border-neutral-200 bg-emerald-50 p-3">
                    <span className="text-sm font-medium">{product.cheapest.platform ?? 'ราคาอ้างอิง'} · คุ้มสุด</span>
                    <span className="font-medium">฿{product.cheapest.price.toLocaleString()}</span>
                </div>
                {product.otherStoreCount > 0 && (
                    <div className="border-t border-neutral-200 p-2 text-center text-sm text-neutral-500">
                        ดูอีก {product.otherStoreCount} ร้าน
                    </div>
                )}
            </div>

            {bundle.length > 0 && (
                <>
                    <p className="mt-4 text-sm font-medium">มักซื้อคู่กับ</p>
                    <div className="mt-2 rounded-xl border border-neutral-200">
                        {bundle.map((b) => (
                            <div key={b.id} className="flex items-center justify-between border-b border-neutral-100 p-3 last:border-0">
                                <span className="text-sm">{b.name}</span>
                                <span className="text-sm text-neutral-500">฿{b.price.toLocaleString()}</span>
                            </div>
                        ))}
                        <div className="flex items-center justify-between bg-neutral-50 p-3">
                            <span className="text-sm text-neutral-500">ทั้งชุด · ฿{bundleTotal.toLocaleString()}</span>
                            <button onClick={() => bundle.forEach((b) => router.post(`/plan/items/${b.id}`, {}, { preserveScroll: true }))}
                                className="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm">ใส่ทั้งชุด</button>
                        </div>
                    </div>
                </>
            )}

            <button onClick={() => router.post(`/plan/items/${product.id}`, {}, { preserveScroll: true })}
                className="mt-4 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">+ ใส่ลงแผน</button>
        </AppLayout>
    );
}
