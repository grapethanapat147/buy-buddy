import { useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import AppLayout from '@/Layouts/AppLayout';

const questions = [
    { key: 'cooking', label: 'ทำอาหารเองบ่อยแค่ไหน', options: [['never', 'ไม่ทำเลย'], ['sometimes', 'ทำบ้าง'], ['often', 'ทำบ่อย']] },
    { key: 'laundry', label: 'ซักผ้ายังไง', options: [['own_machine', 'มีเครื่องซัก'], ['hand', 'ซักมือ'], ['service', 'ส่งร้าน']] },
    { key: 'work_style', label: 'ทำงานที่ไหนเป็นหลัก', options: [['office', 'ออฟฟิศ'], ['home', 'ที่ห้อง'], ['hybrid', 'ผสม']] },
    { key: 'spending_style', label: 'สไตล์การซื้อของ', options: [['essentials', 'เอาที่จำเป็น'], ['balanced', 'พอดี ๆ'], ['comfort', 'อยากได้ครบ']] },
];

export default function Wizard() {
    const { data, setData, post, processing } = useForm({
        budget: 5000, room_type: 'studio', occupants: 1,
        cooking: 'sometimes', laundry: 'own_machine', work_style: 'office', spending_style: 'balanced',
    });

    const submit = (e) => { e.preventDefault(); post('/wizard'); };
    const input = 'mt-1.5 w-full rounded-xl border border-ink/10 bg-cream-card p-3 text-base text-ink focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-2xl font-bold text-ink">ตั้งค่าห้องของคุณ</h1>
                <p className="mt-1 text-base text-ink-soft">ตอบสั้น ๆ เพื่อให้เราแนะนำได้ตรงใจ</p>

                <label className="mt-6 block text-base font-medium text-ink">งบประมาณ (฿)</label>
                <input type="number" value={data.budget} onChange={(e) => setData('budget', Number(e.target.value))} className={input} />

                <label className="mt-5 block text-base font-medium text-ink">อยู่กี่คน</label>
                <input type="number" min="1" value={data.occupants} onChange={(e) => setData('occupants', Number(e.target.value))} className={input} />

                {questions.map((q) => (
                    <div key={q.key} className="mt-6">
                        <p className="text-base font-medium text-ink">{q.label}</p>
                        <div className="mt-2 grid grid-cols-3 gap-2">
                            {q.options.map(([val, label]) => {
                                const active = data[q.key] === val;
                                return (
                                    <button
                                        key={val}
                                        type="button"
                                        aria-pressed={active}
                                        onClick={() => setData(q.key, val)}
                                        className={`rounded-2xl border p-3 text-base transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${
                                            active ? 'border-2 border-brand bg-brand-50 font-semibold text-brand-700' : 'border-ink/10 text-ink-soft hover:bg-cream-sunk'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}

                <motion.button whileTap={{ scale: 0.98 }} type="submit" disabled={processing}
                    className="mt-8 w-full rounded-full bg-brand p-4 text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 disabled:opacity-60">
                    ดูชุดของแนะนำ
                </motion.button>
            </form>
        </AppLayout>
    );
}
