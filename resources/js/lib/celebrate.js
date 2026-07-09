import confetti from 'canvas-confetti';

export function celebrate(origin = { x: 0.5, y: 0.4 }) {
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    confetti({
        particleCount: 70,
        spread: 65,
        startVelocity: 32,
        origin,
        colors: ['#FF6B5E', '#FF8A6E', '#FFC7A6', '#10B981', '#FFF1EE'],
        scalar: 0.9,
    });
}
