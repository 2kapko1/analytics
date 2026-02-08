import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { useState, useMemo } from 'react';
import { Eye, ArrowUpDown } from 'lucide-react';

interface TimeSeriesDataPoint {
    date: string;
    count: number;
}

interface UrlStat {
    url: string;
    count: number;
}

interface DashboardProps {
    totalPageViews: number;
    timeSeriesData: TimeSeriesDataPoint[];
    urlStats: UrlStat[];
}

type SortField = 'url' | 'count';
type SortDirection = 'asc' | 'desc';

export default function Dashboard({
    totalPageViews,
    timeSeriesData,
    urlStats,
}: DashboardProps) {
    const [sortField, setSortField] = useState<SortField>('count');
    const [sortDirection, setSortDirection] = useState<SortDirection>('desc');

    const handleSort = (field: SortField) => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('desc');
        }
    };

    const sortedUrlStats = useMemo(() => {
        return [...urlStats].sort((a, b) => {
            let comparison = 0;
            if (sortField === 'url') {
                comparison = a.url.localeCompare(b.url);
            } else if (sortField === 'count') {
                comparison = a.count - b.count;
            }
            return sortDirection === 'asc' ? comparison : -comparison;
        });
    }, [urlStats, sortField, sortDirection]);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const chartData = timeSeriesData.map((item) => ({
        ...item,
        formattedDate: formatDate(item.date),
    }));

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Analytics Dashboard
                </h2>
            }
        >
            <Head title="Analytics Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Summary Card */}
                    <div className="grid gap-4 md:grid-cols-1 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Page Views
                                </CardTitle>
                                <Eye className="h-4 w-4 text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {totalPageViews.toLocaleString()}
                                </div>
                                <CardDescription>
                                    Unique page views tracked
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Chart */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle>Traffic Over Time</CardTitle>
                            <CardDescription>
                                Page views over the last 30 days
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={350}>
                                    <LineChart data={chartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis
                                            dataKey="formattedDate"
                                            tick={{ fontSize: 12 }}
                                        />
                                        <YAxis tick={{ fontSize: 12 }} />
                                        <Tooltip />
                                        <Legend />
                                        <Line
                                            type="monotone"
                                            dataKey="count"
                                            stroke="#3b82f6"
                                            strokeWidth={2}
                                            name="Page Views"
                                            dot={{ r: 3 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-[350px] text-gray-500">
                                    No data available for the selected period
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Data Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>URL Statistics</CardTitle>
                            <CardDescription>
                                Detailed breakdown of page views by URL
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {sortedUrlStats.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50"
                                                onClick={() => handleSort('url')}
                                            >
                                                <div className="flex items-center gap-2">
                                                    URL
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50 text-right"
                                                onClick={() => handleSort('count')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Page Views
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {sortedUrlStats.map((stat, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium max-w-md truncate">
                                                    {stat.url}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {stat.count.toLocaleString()}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="flex items-center justify-center h-32 text-gray-500">
                                    No URL statistics available
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
