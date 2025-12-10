import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Error Boundary caught an error:', error, errorInfo);
        this.setState({
            error,
            errorInfo
        });
    }

    handleReset = () => {
        this.setState({ hasError: false, error: null, errorInfo: null });
        window.location.href = '/';
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen bg-gradient-to-br from-red-50 via-orange-50/30 to-red-100 flex items-center justify-center p-4">
                    <div className="max-w-2xl w-full bg-white rounded-2xl shadow-2xl p-8">
                        {/* Error Icon */}
                        <div className="flex justify-center mb-6">
                            <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                                <svg
                                    className="w-12 h-12 text-red-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                        </div>

                        {/* Error Message */}
                        <h1 className="text-3xl font-black text-gray-900 text-center mb-4">
                            Oops! Something went wrong
                        </h1>

                        <p className="text-gray-600 text-center mb-6">
                            We encountered an unexpected error while rendering this page.
                            Don't worry, your data is safe.
                        </p>

                        {/* Error Details (Development Mode) */}
                        {import.meta.env.MODE === 'development' && this.state.error && (
                            <details className="mb-6 bg-gray-50 border-2 border-gray-200 rounded-lg p-4">
                                <summary className="cursor-pointer font-semibold text-gray-700 hover:text-gray-900">
                                    View Error Details (Development Only)
                                </summary>
                                <div className="mt-4 space-y-4">
                                    <div>
                                        <h3 className="font-semibold text-sm text-red-600 mb-2">Error Message:</h3>
                                        <pre className="bg-white p-3 rounded border border-red-200 text-xs overflow-x-auto">
                                            {this.state.error.toString()}
                                        </pre>
                                    </div>
                                    {this.state.errorInfo && (
                                        <div>
                                            <h3 className="font-semibold text-sm text-red-600 mb-2">Component Stack:</h3>
                                            <pre className="bg-white p-3 rounded border border-red-200 text-xs overflow-x-auto max-h-64">
                                                {this.state.errorInfo.componentStack}
                                            </pre>
                                        </div>
                                    )}
                                </div>
                            </details>
                        )}

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-center">
                            <button
                                onClick={this.handleReset}
                                className="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl"
                            >
                                Return to Dashboard
                            </button>
                            <button
                                onClick={() => window.location.reload()}
                                className="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-all border-2 border-gray-300"
                            >
                                Reload Page
                            </button>
                        </div>

                        {/* Help Text */}
                        <p className="text-center text-sm text-gray-500 mt-6">
                            If this problem persists, please contact{' '}
                            <a href="mailto:support@university.edu" className="text-blue-600 hover:underline">
                                technical support
                            </a>
                        </p>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
