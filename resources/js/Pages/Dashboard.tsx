import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
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
    visits: number;
    uniqueVisits: number;
}

interface UrlStat {
    url: string;
    totalVisits: number;
    totalUniqueVisits: number;
    todayVisits: number;
    todayUniqueVisits: number;
    monthVisits: number;
    monthUniqueVisits: number;
}

interface DashboardProps {
    basePaths: string[];
    currentBasePath: string | null;
    totalPageViews: number;
    totalsToday: {
        visits: number;
        uniqueVisits: number;
    };
    totalsMonth: {
        visits: number;
        uniqueVisits: number;
    };
    timeSeriesData: TimeSeriesDataPoint[];
    urlStats: UrlStat[];
}

type SortField =
    | 'url'
    | 'todayVisits'
    | 'todayUniqueVisits'
    | 'monthVisits'
    | 'monthUniqueVisits'
    | 'totalVisits'
    | 'totalUniqueVisits';
type SortDirection = 'asc' | 'desc';

export default function Dashboard({
    basePaths,
    currentBasePath,
    totalPageViews,
    totalsToday,
    totalsMonth,
    timeSeriesData,
    urlStats,
}: DashboardProps) {
    const [sortField, setSortField] = useState<SortField>('monthUniqueVisits');
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
            } else {
                comparison = a[sortField] - b[sortField];
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
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 mb-6">
                    <div className="flex items-center gap-3">
                        <label htmlFor="base-path-filter" className="text-sm font-medium text-gray-700">
                            Filter by domain:
                        </label>
                        <select
                            id="base-path-filter"
                            value={currentBasePath ?? ''}
                            onChange={(e) => {
                                const value = e.target.value;
                                router.get(
                                    route('dashboard'),
                                    value ? { base_path: value } : {},
                                    { preserveState: true, preserveScroll: true },
                                );
                            }}
                            className="rounded-md border w-1/2 border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">All domains</option>
                            {basePaths.map((path) => (
                                <option key={path} value={path}>
                                    {path}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Summary Card */}
                    <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-3 mb-8">
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

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Today
                                </CardTitle>
                                <Eye className="h-4 w-4 text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {totalsToday.visits.toLocaleString()}
                                </div>
                                <CardDescription>
                                    Visits • {totalsToday.uniqueVisits.toLocaleString()} unique
                                </CardDescription>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    This Month
                                </CardTitle>
                                <Eye className="h-4 w-4 text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {totalsMonth.visits.toLocaleString()}
                                </div>
                                <CardDescription>
                                    Visits • {totalsMonth.uniqueVisits.toLocaleString()} unique
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
                                            dataKey="visits"
                                            stroke="#3b82f6"
                                            strokeWidth={2}
                                            name="Visits"
                                            dot={{ r: 3 }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="uniqueVisits"
                                            stroke="#10b981"
                                            strokeWidth={2}
                                            name="Unique Visits"
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
                                                onClick={() => handleSort('todayVisits')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Today Visits
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50 text-right"
                                                onClick={() => handleSort('todayUniqueVisits')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Today Unique
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50 text-right"
                                                onClick={() => handleSort('monthVisits')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Month Visits
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50 text-right"
                                                onClick={() => handleSort('monthUniqueVisits')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Month Unique
                                                    <ArrowUpDown className="h-4 w-4" />
                                                </div>
                                            </TableHead>
                                            <TableHead
                                                className="cursor-pointer hover:bg-gray-50 text-right"
                                                onClick={() => handleSort('totalUniqueVisits')}
                                            >
                                                <div className="flex items-center justify-end gap-2">
                                                    Total Unique
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
                                                    {stat.todayVisits.toLocaleString()}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {stat.todayUniqueVisits.toLocaleString()}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {stat.monthVisits.toLocaleString()}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {stat.monthUniqueVisits.toLocaleString()}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {stat.totalUniqueVisits.toLocaleString()}
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
