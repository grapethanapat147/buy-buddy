import { useEffect, useState } from 'react';
import { motion, animate, useReducedMotion } from 'motion/react';

const EASE = [0.22, 1, 0.36, 1];

export default function BudgetMeter({ total, budget }) {
    const reduce = useReducedMotion();
    const over = total > budget;
    const pct = Math.min(100, Math.round((total / budget) * 100));
    const near = !over && pct >= 85;

    const fill = over ? '#F43F5E' : near ? '#F59E0B' : '#10B981';
    const textColor = over ? 'text-rose-600' : near ? 'text-amber-600' : 'text-emerald-600';
    const valueText = over
        ? `฿${total.toLocaleString()} เกินงบ ฿${(total - budget).toLocaleString()}`
        : `฿${total.toLocaleString()} จากงบ ฿${budget.toLocaleString()}`;

    const [display, setDisplay] = useState(total);
    useEffect(() => {
        if (reduce) {
            setDisplay(total);
            return;
        }
        const controls = animate(display, total, {
            duration: 0.5,
            ease: EASE,
            onUpdate: (v) => setDisplay(Math.round(v)),
        });
        return () => controls.stop();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [total, reduce]);

    return (
        <div>
            <div className="flex items-baseline justify-between text-sm">
                <span className="text-ink-soft">งบของฉัน</span>
                <span className="tabular-nums">
                    <span className={`font-semibold ${textColor}`}>฿{display.toLocaleString()}</span>
                    <span className="text-ink-muted"> / ฿{budget.toLocaleString()}</span>
                </span>
            </div>
            <div
                className="mt-1.5 h-2.5 overflow-hidden rounded-full bg-ink/10"
                role="progressbar"
                aria-valuenow={total}
                aria-valuemin={0}
                aria-valuemax={budget}
                aria-valuetext={valueText}
            >
                <motion.div
                    className="h-full rounded-full"
                    style={{ backgroundColor: fill }}
                    initial={false}
                    animate={{ width: `${pct}%`, backgroundColor: fill }}
                    transition={{ duration: 0.5, ease: EASE }}
                />
            </div>
        </div>
    );
}
