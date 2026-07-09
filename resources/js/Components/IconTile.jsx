export default function IconTile({ icon, size = 'md' }) {
    const s = size === 'lg' ? 'h-16 w-16 text-3xl' : 'h-11 w-11 text-2xl';
    return (
        <div className={`flex ${s} shrink-0 items-center justify-center rounded-2xl bg-cream-sunk`} aria-hidden="true">
            {icon}
        </div>
    );
}
