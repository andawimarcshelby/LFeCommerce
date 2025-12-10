export default function LoadingSpinner({ size = 'md', message = 'Loading...' }) {
    const sizes = {
        sm: 'w-8 h-8',
        md: 'w-16 h-16',
        lg: 'w-24 h-24',
    };

    const textSizes = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
    };

    return (
        <div className="flex flex-col items-center justify-center">
            <div className={`${sizes[size]} border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4`}></div>
            {message && (
                <p className={`${textSizes[size]} text-gray-600 font-semibold`}>{message}</p>
            )}
        </div>
    );
}

export function PageLoader({ message }) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-gray-100 flex items-center justify-center">
            <LoadingSpinner size="lg" message={message} />
        </div>
    );
}

export function InlineLoader({ message }) {
    return (
        <div className="flex items-center justify-center p-8">
            <LoadingSpinner size="sm" message={message} />
        </div>
    );
}
