import { useState, useEffect } from 'react';

export default function ExportProgressBar({
    jobId,
    status,
    progress,
    currentSection,
    processedRows,
    totalRows,
    onDownload,
    onCancel,
    onRetry
}) {
    const [eta, setEta] = useState(null);
    const [startTime] = useState(Date.now());

    // Calculate ETA based on progress rate
    useEffect(() => {
        if (status === 'running' && progress > 0 && progress < 100) {
            const elapsed = (Date.now() - startTime) / 1000; // seconds
            const rate = progress / elapsed; // percent per second
            const remaining = (100 - progress) / rate; // seconds remaining

            if (remaining < 60) {
                setEta(`~${Math.ceil(remaining)} seconds`);
            } else {
                setEta(`~${Math.ceil(remaining / 60)} minutes`);
            }
        } else {
            setEta(null);
        }
    }, [progress, status, startTime]);

    // Status configuration
    const statusConfig = {
        queued: {
            color: 'bg-gray-100 text-gray-800 border-gray-300',
            icon: '⏳',
            label: 'Queued',
            barColor: 'bg-gray-400',
            animated: false
        },
        running: {
            color: 'bg-blue-100 text-blue-800 border-blue-300',
            icon: '⚙️',
            label: 'Processing',
            barColor: 'bg-gradient-to-r from-blue-500 to-blue-600',
            animated: true
        },
        completed: {
            color: 'bg-green-100 text-green-800 border-green-300',
            icon: '✓',
            label: 'Completed',
            barColor: 'bg-green-500',
            animated: false
        },
        failed: {
            color: 'bg-red-100 text-red-800 border-red-300',
            icon: '✕',
            label: 'Failed',
            barColor: 'bg-red-500',
            animated: false
        }
    };

    const config = statusConfig[status] || statusConfig.queued;

    return (
        <div className="bg-white rounded-lg border-2 border-gray-200 p-4 space-y-3">
            {/* Header: Status Badge + Job ID */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border-2 ${config.color}`}>
                        <span>{config.icon}</span>
                        <span className="uppercase">{config.label}</span>
                    </span>
                    <span className="text-sm text-gray-500 font-mono">#{jobId}</span>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-2">
                    {status === 'completed' && onDownload && (
                        <button
                            onClick={() => onDownload(jobId)}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold text-sm flex items-center gap-2"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            Download
                        </button>
                    )}

                    {(status === 'queued' || status === 'running') && onCancel && (
                        <button
                            onClick={() => onCancel(jobId)}
                            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold text-sm"
                        >
                            Cancel
                        </button>
                    )}

                    {status === 'failed' && onRetry && (
                        <button
                            onClick={() => onRetry(jobId)}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm"
                        >
                            Retry
                        </button>
                    )}
                </div>
            </div>

            {/* Progress Bar (for running/queued) */}
            {(status === 'running' || status === 'queued') && (
                <div>
                    <div className="flex items-center justify-between text-xs mb-2">
                        <span className="font-semibold text-gray-700">
                            {progress}% Complete
                        </span>
                        <span className="text-gray-500">
                            {currentSection || 'Initializing...'}
                        </span>
                    </div>

                    {/* Progress bar */}
                    <div className="h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            className={`h-full ${config.barColor} transition-all duration-500 ${config.animated ? 'progress-bar-animated' : ''}`}
                            style={{ width: `${progress}%` }}
                        />
                    </div>

                    {/* ETA and Row Count */}
                    <div className="flex items-center justify-between text-xs mt-2 text-gray-600">
                        <span>
                            {processedRows?.toLocaleString()} / {totalRows?.toLocaleString()} rows
                        </span>
                        {eta && (
                            <span className="font-semibold text-blue-600">
                                {eta} remaining
                            </span>
                        )}
                    </div>
                </div>
            )}

            {/* Completed: Show file size */}
            {status === 'completed' && totalRows && (
                <div className="text-sm text-gray-600">
                    <span className="font-semibold">{totalRows.toLocaleString()}</span> rows exported
                </div>
            )}

            {/* Failed: Show retry message */}
            {status === 'failed' && (
                <div className="text-sm text-red-600 font-medium">
                    Export failed. Please try again or contact support.
                </div>
            )}

            <style jsx>{`
                @keyframes progress-shimmer {
                    0% { background-position: -200% 0; }
                    100% { background-position: 200% 0; }
                }
                .progress-bar-animated {
                    background-size: 200% 100%;
                    animation: progress-shimmer 2s linear infinite;
                }
            `}</style>
        </div>
    );
}
