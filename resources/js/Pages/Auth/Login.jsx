import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });
    const submit = (e) => { e.preventDefault(); post('/login'); };
    const field = 'mt-1 w-full rounded-xl border border-ink/10 bg-cream-card px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-semibold text-ink">เข้าสู่ระบบ</h1>
                <label className="mt-4 block text-sm text-ink-soft">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-ink-soft">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-full bg-brand p-3 font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98] focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:outline-none disabled:opacity-60">เข้าสู่ระบบ</button>
                <Link href="/register" className="mt-3 block text-center text-sm text-ink-soft transition-colors hover:text-brand">ยังไม่มีบัญชี? สมัคร</Link>
            </form>
        </AppLayout>
    );
}
