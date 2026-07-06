import { useForm } from '@inertiajs/react';
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

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">ตั้งค่าห้องของคุณ</h1>
                <label className="mt-4 block text-sm text-neutral-600">งบประมาณ (฿)</label>
                <input type="number" value={data.budget} onChange={(e) => setData('budget', Number(e.target.value))}
                    className="mt-1 w-full rounded-lg border border-neutral-200 p-2" />
                <label className="mt-4 block text-sm text-neutral-600">อยู่กี่คน</label>
                <input type="number" min="1" value={data.occupants} onChange={(e) => setData('occupants', Number(e.target.value))}
                    className="mt-1 w-full rounded-lg border border-neutral-200 p-2" />
                <p className="mt-4 text-sm text-neutral-600">ทำอาหารเองบ่อยแค่ไหน</p>
                <div className="mt-2 space-y-2">
                    {cook.map((c) => (
                        <button type="button" key={c.v} onClick={() => setData('cooking', c.v)}
                            className={`block w-full rounded-xl border p-3 text-left ${data.cooking === c.v ? 'border-2 border-sky-500 bg-sky-50' : 'border-neutral-200'}`}>
                            {c.label}
                        </button>
                    ))}
                </div>
                <button type="submit" disabled={processing}
                    className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">
                    ดูชุดของแนะนำ
                </button>
            </form>
        </AppLayout>
    );
}
