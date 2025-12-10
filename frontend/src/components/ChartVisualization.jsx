import { BarChart, Bar, LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const COLORS = {
    primary: ['#750E21', '#E3651D', '#BED754', '#191919'],
    success: '#BED754',
    warning: '#E3651D',
    danger: '#750E21',
    dark: '#191919',
};

export default function ChartVisualization({ data, chartType, title, xKey, yKey, colorScheme }) {
    const colors = colorScheme || COLORS.primary;

    const renderChart = () => {
        switch (chartType) {
            case 'bar':
                return (
                    <BarChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                        <XAxis
                            dataKey={xKey}
                            stroke="#6b7280"
                            style={{ fontSize: '12px', fontWeight: '500' }}
                        />
                        <YAxis
                            stroke="#6b7280"
                            style={{ fontSize: '12px', fontWeight: '500' }}
                        />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'white',
                                border: '1px solid #e5e7eb',
                                borderRadius: '8px',
                                padding: '12px',
                                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                            }}
                        />
                        <Legend
                            wrapperStyle={{ fontSize: '14px', fontWeight: '600' }}
                        />
                        <Bar
                            dataKey={yKey}
                            fill={colors[0]}
                            radius={[8, 8, 0, 0]}
                        />
                    </BarChart>
                );

            case 'line':
                return (
                    <LineChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                        <XAxis
                            dataKey={xKey}
                            stroke="#6b7280"
                            style={{ fontSize: '12px', fontWeight: '500' }}
                        />
                        <YAxis
                            stroke="#6b7280"
                            style={{ fontSize: '12px', fontWeight: '500' }}
                        />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'white',
                                border: '1px solid #e5e7eb',
                                borderRadius: '8px',
                                padding: '12px',
                                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                            }}
                        />
                        <Legend
                            wrapperStyle={{ fontSize: '14px', fontWeight: '600' }}
                        />
                        <Line
                            type="monotone"
                            dataKey={yKey}
                            stroke={colors[0]}
                            strokeWidth={3}
                            dot={{ fill: colors[0], strokeWidth: 2, r: 4 }}
                            activeDot={{ r: 6 }}
                        />
                    </LineChart>
                );

            case 'pie':
                return (
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                            outerRadius={120}
                            fill="#8884d8"
                            dataKey={yKey}
                        >
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
                            ))}
                        </Pie>
                        <Tooltip
                            contentStyle={{
                                backgroundColor: 'white',
                                border: '1px solid #e5e7eb',
                                borderRadius: '8px',
                                padding: '12px',
                                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                            }}
                        />
                        <Legend
                            wrapperStyle={{ fontSize: '14px', fontWeight: '600' }}
                        />
                    </PieChart>
                );

            default:
                return <div className="text-red-600 font-semibold">Unknown chart type: {chartType}</div>;
        }
    };

    return (
        <div className="bg-white rounded-xl shadow-lg p-6">
            {/* Title */}
            {title && (
                <h3 className="text-xl font-bold text-gray-900 mb-6">{title}</h3>
            )}

            {/* Chart */}
            <ResponsiveContainer width="100%" height={400}>
                {renderChart()}
            </ResponsiveContainer>
        </div>
    );
}
