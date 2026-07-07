import { useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Wizard() {
    const { data, setData, post, processing } = useForm({
        budget: 5000, room_type: 'studio', occupants: 1, cooking: 'sometimes',
    });

    const submit = (e) => { e.preventDefault(); post('/wizard'); };
    const cook = [
        { v: 'never', label: 'ไม่ทำเลย' },
        { v: 'sometimes', label: 'ทำบ้าง' },
        { v: 'often', label: 'ทำบ่อย' },
    ];

    const cookProgress = { never: 33, sometimes: 66, often: 100 }[data.cooking] ?? 0;
    const field = 'mt-1 w-full rounded-xl border border-ink/10 bg-cream-card px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-semibold text-ink">ตั้งค่าห้องของคุณ</h1>

                <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-ink/10">
                    <div
                        className="h-full rounded-full bg-brand transition-[width] duration-300 ease-out"
                        style={{ width: `${cookProgress}%` }}
                    />
                </div>

                <label className="mt-4 block text-sm text-ink-soft">งบประมาณ (฿)</label>
                <input type="number" value={data.budget} onChange={(e) => setData('budget', Number(e.target.value))}
                    className={field} />
                <label className="mt-4 block text-sm text-ink-soft">อยู่กี่คน</label>
                <input type="number" min="1" value={data.occupants} onChange={(e) => setData('occupants', Number(e.target.value))}
                    className={field} />
                <p className="mt-4 text-sm text-ink-soft">ทำอาหารเองบ่อยแค่ไหน</p>
                <div className="mt-2 space-y-2">
                    {cook.map((c) => {
                        const selected = data.cooking === c.v;
                        return (
                            <button type="button" key={c.v} onClick={() => setData('cooking', c.v)}
                                className={`block w-full rounded-xl p-3 text-left text-sm transition focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none ${selected ? 'border-2 border-brand bg-brand-50 font-semibold text-ink' : 'border border-ink/10 text-ink-soft hover:bg-cream-sunk'}`}>
                                {c.label}
                            </button>
                        );
                    })}
                </div>
                <motion.button type="submit" disabled={processing} whileTap={{ scale: 0.98 }}
                    className="mt-6 w-full rounded-full bg-brand p-3 font-semibold text-white shadow-soft transition hover:bg-brand-500 focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none disabled:opacity-60">
                    ดูชุดของแนะนำ
                </motion.button>
            </form>
        </AppLayout>
    );
}
