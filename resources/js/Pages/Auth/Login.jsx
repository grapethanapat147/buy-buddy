import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });
    const submit = (e) => { e.preventDefault(); post('/login'); };
    const field = 'mt-1 w-full rounded-lg border border-neutral-200 p-2';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">เข้าสู่ระบบ</h1>
                <label className="mt-4 block text-sm text-neutral-600">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-neutral-600">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">เข้าสู่ระบบ</button>
                <Link href="/register" className="mt-3 block text-center text-sm text-neutral-500">ยังไม่มีบัญชี? สมัคร</Link>
            </form>
        </AppLayout>
    );
}
