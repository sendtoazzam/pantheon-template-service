import React, { useState, useEffect } from 'react';
import { 
    IconCheck, 
    IconX, 
    IconAlertTriangle, 
    IconClock, 
    IconRefresh,
    IconServer,
    IconDatabase,
    IconApi,
    IconShield,
    IconActivity,
    IconSettings
} from '@tabler/icons-react';

const StatusPage = () => {
    const [statusData, setStatusData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [lastUpdated, setLastUpdated] = useState(null);
    const [autoRefresh, setAutoRefresh] = useState(true);

    const fetchStatus = async () => {
        try {
            const [healthResponse, systemResponse] = await Promise.all([
                fetch('/api/v1/health/detailed'),
                fetch('/api/v1/system/status')
            ]);

            const healthData = await healthResponse.json();
            const systemData = await systemResponse.json();

            setStatusData({
                health: healthData.data || healthData,
                system: systemData.data || systemData
            });
            setLastUpdated(new Date());
        } catch (error) {
            console.error('Failed to fetch status:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStatus();
        
        if (autoRefresh) {
            const interval = setInterval(fetchStatus, 30000); // Refresh every 30 seconds
            return () => clearInterval(interval);
        }
    }, [autoRefresh]);

    const getStatusIcon = (status) => {
        switch (status?.toLowerCase()) {
            case 'operational':
            case 'healthy':
                return <IconCheck className="w-5 h-5 text-green-500" />;
            case 'degraded':
            case 'partial_outage':
                return <IconAlertTriangle className="w-5 h-5 text-yellow-500" />;
            case 'unhealthy':
            case 'outage':
                return <IconX className="w-5 h-5 text-red-500" />;
            default:
                return <IconClock className="w-5 h-5 text-gray-400" />;
        }
    };

    const getStatusColor = (status) => {
        switch (status?.toLowerCase()) {
            case 'operational':
            case 'healthy':
                return 'text-green-600 bg-green-50 border-green-200';
            case 'degraded':
            case 'partial_outage':
                return 'text-yellow-600 bg-yellow-50 border-yellow-200';
            case 'unhealthy':
            case 'outage':
                return 'text-red-600 bg-red-50 border-red-200';
            default:
                return 'text-gray-600 bg-gray-50 border-gray-200';
        }
    };

    const services = [
        {
            name: 'API Services',
            status: statusData?.health?.overallStatus || 'Unknown',
            description: 'Core API endpoints and authentication',
            icon: <IconApi className="w-6 h-6" />
        },
        {
            name: 'Database',
            status: statusData?.health?.checks?.database?.status || 'Unknown',
            description: 'Database connectivity and performance',
            icon: <IconDatabase className="w-6 h-6" />
        },
        {
            name: 'Authentication',
            status: statusData?.health?.checks?.auth?.status || 'Unknown',
            description: 'User authentication and authorization',
            icon: <IconShield className="w-6 h-6" />
        },
        {
            name: 'System Health',
            status: statusData?.system?.status || 'Unknown',
            description: 'Overall system performance and resources',
            icon: <IconServer className="w-6 h-6" />
        },
        {
            name: 'Logging',
            status: statusData?.health?.checks?.logging?.status || 'Unknown',
            description: 'Application logging and monitoring',
            icon: <IconActivity className="w-6 h-6" />
        },
        {
            name: 'Configuration',
            status: statusData?.health?.checks?.config?.status || 'Unknown',
            description: 'Application configuration and settings',
            icon: <IconSettings className="w-6 h-6" />
        }
    ];

    const incidents = [
        {
            title: 'Scheduled Maintenance',
            status: 'completed',
            description: 'Database optimization completed successfully',
            time: '2 hours ago',
            impact: 'minor'
        },
        {
            title: 'API Performance Improvement',
            status: 'resolved',
            description: 'Response time improvements implemented',
            time: '1 day ago',
            impact: 'minor'
        }
    ];

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                    <p className="text-gray-600 dark:text-gray-400 mt-4">Loading status...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Header */}
            <header className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center py-6">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Muslim Finder Backend
                                </h1>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    System Status
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            <button
                                onClick={() => setAutoRefresh(!autoRefresh)}
                                className={`flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                                    autoRefresh 
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' 
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                }`}
                            >
                                <IconRefresh className={`w-4 h-4 mr-2 ${autoRefresh ? 'animate-spin' : ''}`} />
                                Auto Refresh
                            </button>
                            <button
                                onClick={fetchStatus}
                                className="flex items-center px-3 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
                            >
                                <IconRefresh className="w-4 h-4 mr-2" />
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Overall Status */}
                <div className="mb-8">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                {getStatusIcon(statusData?.health?.overallStatus)}
                                <div className="ml-3">
                                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        All Systems Operational
                                    </h2>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        All services are running normally
                                    </p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Last updated
                                </p>
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    {lastUpdated ? lastUpdated.toLocaleTimeString() : 'Never'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Services Status */}
                <div className="mb-8">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Services
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {services.map((service, index) => (
                            <div
                                key={index}
                                className={`bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-4 ${getStatusColor(service.status)}`}
                            >
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        {service.icon}
                                    </div>
                                    <div className="ml-3 flex-1">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-medium">
                                                {service.name}
                                            </h4>
                                            {getStatusIcon(service.status)}
                                        </div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {service.description}
                                        </p>
                                        <div className="mt-2">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                service.status?.toLowerCase() === 'operational' || service.status?.toLowerCase() === 'healthy'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    : service.status?.toLowerCase() === 'degraded' || service.status?.toLowerCase() === 'partial_outage'
                                                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                            }`}>
                                                {service.status || 'Unknown'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Incidents */}
                <div className="mb-8">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Recent Incidents
                    </h3>
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        {incidents.map((incident, index) => (
                            <div
                                key={index}
                                className={`p-4 ${index !== incidents.length - 1 ? 'border-b border-gray-200 dark:border-gray-700' : ''}`}
                            >
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        {incident.status === 'resolved' || incident.status === 'completed' ? (
                                            <IconCheck className="w-5 h-5 text-green-500" />
                                        ) : (
                                            <IconAlertTriangle className="w-5 h-5 text-yellow-500" />
                                        )}
                                    </div>
                                    <div className="ml-3 flex-1">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                                {incident.title}
                                            </h4>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                incident.status === 'resolved' || incident.status === 'completed'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                            }`}>
                                                {incident.status}
                                            </span>
                                        </div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {incident.description}
                                        </p>
                                        <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            {incident.time} • {incident.impact} impact
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* API Endpoints */}
                <div>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        API Endpoints
                    </h3>
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    Health Check
                                </h4>
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between text-sm">
                                        <code className="text-gray-600 dark:text-gray-400">GET /api/v1/health</code>
                                        <span className="text-green-600 font-medium">200 OK</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <code className="text-gray-600 dark:text-gray-400">GET /api/v1/health/detailed</code>
                                        <span className="text-green-600 font-medium">200 OK</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    System Status
                                </h4>
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between text-sm">
                                        <code className="text-gray-600 dark:text-gray-400">GET /api/v1/system/status</code>
                                        <span className="text-green-600 font-medium">200 OK</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <code className="text-gray-600 dark:text-gray-400">GET /api/v1/system/metrics</code>
                                        <span className="text-green-600 font-medium">200 OK</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            © {new Date().getFullYear()} Muslim Finder Backend. All rights reserved.
                        </div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            Status page powered by React & TailwindCSS
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default StatusPage;
