const faces = { happy: '🛍️', celebrate: '🎉', thinking: '🤔', caring: '🤗' };

export default function Mascot({ mood = 'happy', className = '' }) {
    return (
        <span className={className} role="img" aria-label={`mascot ${mood}`}>
            {faces[mood] ?? faces.happy}
        </span>
    );
}
