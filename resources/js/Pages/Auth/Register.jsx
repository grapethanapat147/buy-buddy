import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const submit = (e) => { e.preventDefault(); post('/register'); };
    const field = 'mt-1 w-full rounded-xl border border-ink/10 bg-cream-card px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-2xl font-semibold text-ink">สมัครเพื่อเซฟแผน</h1>
                <p className="mt-1 text-sm text-ink-soft">แผนที่จัดไว้จะถูกเก็บให้อัตโนมัติ ไม่หาย</p>
                <label className="mt-4 block text-sm text-ink-soft">ชื่อ</label>
                <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={field} />
                {errors.name && <p className="text-sm text-rose-600">{errors.name}</p>}
                <label className="mt-3 block text-sm text-ink-soft">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-ink-soft">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                {errors.password && <p className="text-sm text-rose-600">{errors.password}</p>}
                <label className="mt-3 block text-sm text-ink-soft">ยืนยันรหัสผ่าน</label>
                <input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-full bg-brand p-4 text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98] focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none disabled:opacity-60">สมัครและเซฟแผน</button>
            </form>
        </AppLayout>
    );
}
