import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const submit = (e) => { e.preventDefault(); post('/register'); };
    const field = 'mt-1 w-full rounded-lg border border-neutral-200 p-2';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">สมัครเพื่อเซฟแผน</h1>
                <p className="mt-1 text-sm text-neutral-500">แผนที่จัดไว้จะถูกเก็บให้อัตโนมัติ ไม่หาย</p>
                <label className="mt-4 block text-sm text-neutral-600">ชื่อ</label>
                <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={field} />
                {errors.name && <p className="text-sm text-rose-600">{errors.name}</p>}
                <label className="mt-3 block text-sm text-neutral-600">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-neutral-600">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                {errors.password && <p className="text-sm text-rose-600">{errors.password}</p>}
                <label className="mt-3 block text-sm text-neutral-600">ยืนยันรหัสผ่าน</label>
                <input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">สมัครและเซฟแผน</button>
            </form>
        </AppLayout>
    );
}
