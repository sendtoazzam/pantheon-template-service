import React, { useState, useEffect } from 'react';
import { 
    IconCheck,
    IconAlertTriangle,
    IconX,
    IconRefresh,
    IconChartBar,
    IconServer,
    IconDatabase,
    IconCpu
} from '@tabler/icons-react';

const HealthDashboard = () => {
    const [healthData, setHealthData] = useState(null);
    const [quickStatus, setQuickStatus] = useState(null);
    const [metrics, setMetrics] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [lastUpdated, setLastUpdated] = useState(null);

    const fetchHealthData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const [statusResponse, quickResponse, metricsResponse] = await Promise.all([
                fetch('/api/v1/system/status'),
                fetch('/api/v1/system/quick-status'),
                fetch('/api/v1/system/metrics')
            ]);

            if (!statusResponse.ok || !quickResponse.ok || !metricsResponse.ok) {
                throw new Error('Failed to fetch health data');
            }

            const [statusData, quickData, metricsData] = await Promise.all([
                statusResponse.json(),
                quickResponse.json(),
                metricsResponse.json()
            ]);

            setHealthData(statusData.data);
            setQuickStatus(quickData.data);
            setMetrics(metricsData.data);
            setLastUpdated(new Date());
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchHealthData();
        
        // Auto-refresh every 30 seconds
        const interval = setInterval(fetchHealthData, 30000);
        return () => clearInterval(interval);
    }, []);

    const getStatusIcon = (status) => {
        switch (status) {
            case 'healthy':
                return <IconCheck className="h-5 w-5 text-green-500" />;
            case 'degraded':
                return <IconAlertTriangle className="h-5 w-5 text-yellow-500" />;
            case 'unhealthy':
                return <IconX className="h-5 w-5 text-red-500" />;
            default:
                return <IconAlertTriangle className="h-5 w-5 text-gray-500" />;
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'healthy':
                return 'text-green-600 bg-green-100';
            case 'degraded':
                return 'text-yellow-600 bg-yellow-100';
            case 'unhealthy':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    const getStatusBadge = (status) => {
        const colors = getStatusColor(status);
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    if (loading && !healthData) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-md p-4">
                <div className="flex">
                    <IconX className="h-5 w-5 text-red-400" />
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-red-800">Error loading health data</h3>
                        <p className="text-sm text-red-700 mt-1">{error}</p>
                        <button
                            onClick={fetchHealthData}
                            className="mt-2 text-sm text-red-600 hover:text-red-500"
                        >
                            Try again
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">System Health Dashboard</h1>
                    <p className="text-gray-600">Monitor your Pantheon Template Service status</p>
                </div>
                <div className="flex items-center space-x-4">
                    {lastUpdated && (
                        <span className="text-sm text-gray-500">
                            Last updated: {lastUpdated.toLocaleTimeString()}
                        </span>
                    )}
                    <button
                        onClick={fetchHealthData}
                        disabled={loading}
                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <IconRefresh className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Refresh
                    </button>
                </div>
            </div>

            {/* Overall Status */}
            {healthData && (
                <div className="bg-white overflow-hidden shadow rounded-lg">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center">
                            {getStatusIcon(healthData.status)}
                            <div className="ml-3">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Overall System Status
                                </h3>
                                <p className="text-gray-600">{healthData.message}</p>
                            </div>
                            <div className="ml-auto">
                                {getStatusBadge(healthData.status)}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Quick Status Cards */}
            {quickStatus && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <IconServer className="h-6 w-6 text-gray-400" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Database Status
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {quickStatus.database_status}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <IconCpu className="h-6 w-6 text-gray-400" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Memory Usage
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {quickStatus.memory_usage}%
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <IconDatabase className="h-6 w-6 text-gray-400" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Disk Usage
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {quickStatus.disk_usage}%
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <IconChartBar className="h-6 w-6 text-gray-400" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Cache Status
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {quickStatus.cache_status}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Detailed Checks */}
            {healthData && healthData.checks && (
                <div className="bg-white shadow overflow-hidden sm:rounded-md">
                    <div className="px-4 py-5 sm:px-6">
                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                            System Checks
                        </h3>
                        <p className="mt-1 max-w-2xl text-sm text-gray-500">
                            Detailed status of all system components
                        </p>
                    </div>
                    <ul className="divide-y divide-gray-200">
                        {Object.entries(healthData.checks).map(([key, check]) => (
                            <li key={key} className="px-4 py-4 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        {getStatusIcon(check.status)}
                                        <div className="ml-3">
                                            <p className="text-sm font-medium text-gray-900 capitalize">
                                                {key.replace('_', ' ')}
                                            </p>
                                            <p className="text-sm text-gray-500">{check.message}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        {getStatusBadge(check.status)}
                                    </div>
                                </div>
                                {check.details && Object.keys(check.details).length > 0 && (
                                    <div className="mt-2 ml-8">
                                        <dl className="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                            {Object.entries(check.details).map(([detailKey, detailValue]) => (
                                                <div key={detailKey}>
                                                    <dt className="text-sm font-medium text-gray-500 capitalize">
                                                        {detailKey.replace('_', ' ')}
                                                    </dt>
                                                    <dd className="text-sm text-gray-900">
                                                        {typeof detailValue === 'object' 
                                                            ? JSON.stringify(detailValue, null, 2)
                                                            : detailValue
                                                        }
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Recommendations */}
            {healthData && healthData.recommendations && healthData.recommendations.length > 0 && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div className="flex">
                        <IconAlertTriangle className="h-5 w-5 text-yellow-400" />
                        <div className="ml-3">
                            <h3 className="text-sm font-medium text-yellow-800">
                                Recommendations
                            </h3>
                            <div className="mt-2 text-sm text-yellow-700">
                                <ul className="list-disc list-inside space-y-1">
                                    {healthData.recommendations.map((recommendation, index) => (
                                        <li key={index}>{recommendation}</li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* System Information */}
            {healthData && healthData.system_info && (
                <div className="bg-white shadow overflow-hidden sm:rounded-md">
                    <div className="px-4 py-5 sm:px-6">
                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                            System Information
                        </h3>
                        <p className="mt-1 max-w-2xl text-sm text-gray-500">
                            Technical details about your system
                        </p>
                    </div>
                    <div className="px-4 py-5 sm:px-6">
                        <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            {Object.entries(healthData.system_info).map(([key, value]) => (
                                <div key={key}>
                                    <dt className="text-sm font-medium text-gray-500 capitalize">
                                        {key.replace('_', ' ')}
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
            )}
        </div>
    );
};

export default HealthDashboard;
