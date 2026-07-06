export default function BudgetMeter({ total, budget }) {
    const over = total > budget;
    const pct = Math.min(100, Math.round((total / budget) * 100));
    return (
        <div>
            <div className="flex justify-between text-sm">
                <span className="text-neutral-500">งบของฉัน</span>
                <span>
                    <span className={over ? 'font-medium text-rose-600' : 'font-medium text-emerald-600'}>
                        ฿{total.toLocaleString()}
                    </span>{' '}/ ฿{budget.toLocaleString()}
                </span>
            </div>
            <div className="mt-1 h-2.5 overflow-hidden rounded-full bg-neutral-100">
                <div className={`h-full ${over ? 'bg-rose-500' : 'bg-emerald-500'}`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}
