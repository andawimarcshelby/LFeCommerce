import { useState } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

const DEFAULT_PRESETS = [
    { label: 'Last 7 Days', days: 7 },
    { label: 'Last 30 Days', days: 30 },
    { label: 'Last 90 Days', days: 90 },
    { label: 'This Term', days: 120 }, // Approximate semester
];

export default function DateRangePicker({ startDate, endDate, onChange, presets = DEFAULT_PRESETS }) {
    const [showCustom, setShowCustom] = useState(false);

    const handlePresetClick = (days) => {
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - days);
        onChange(start, end);
        setShowCustom(false);
    };

    const handleCustomClick = () => {
        setShowCustom(true);
    };

    const handleClear = () => {
        onChange(null, null);
        setShowCustom(false);
    };

    return (
        <div className="space-y-3">
            {/* Preset Buttons */}
            <div className="flex flex-wrap gap-2">
                {presets.map((preset) => (
                    <button
                        key={preset.label}
                        onClick={() => handlePresetClick(preset.days)}
                        className="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors font-semibold text-sm"
                    >
                        {preset.label}
                    </button>
                ))}
                <button
                    onClick={handleCustomClick}
                    className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-semibold text-sm"
                >
                    Custom Range
                </button>
                {(startDate || endDate) && (
                    <button
                        onClick={handleClear}
                        className="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors font-semibold text-sm"
                    >
                        Clear
                    </button>
                )}
            </div>

            {/* Custom Date Pickers */}
            {showCustom && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Start Date
                        </label>
                        <DatePicker
                            selected={startDate}
                            onChange={(date) => onChange(date, endDate)}
                            selectsStart
                            startDate={startDate}
                            endDate={endDate}
                            maxDate={endDate || new Date()}
                            dateFormat="yyyy-MM-dd"
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium"
                            placeholderText="Select start date"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            End Date
                        </label>
                        <DatePicker
                            selected={endDate}
                            onChange={(date) => onChange(startDate, date)}
                            selectsEnd
                            startDate={startDate}
                            endDate={endDate}
                            minDate={startDate}
                            maxDate={new Date()}
                            dateFormat="yyyy-MM-dd"
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium"
                            placeholderText="Select end date"
                        />
                    </div>
                </div>
            )}

            {/* Selected Range Display */}
            {startDate && endDate && (
                <div className="flex items-center gap-2 p-3 bg-blue-50 border-2 border-blue-200 rounded-lg">
                    <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span className="text-sm font-semibold text-blue-900">
                        {startDate.toLocaleDateString()} → {endDate.toLocaleDateString()}
                    </span>
                </div>
            )}
        </div>
    );
}
