import React, { useState, useEffect } from 'react';
import { IconRocket, IconBrandLaravel, IconBrandReact, IconBrandTailwind } from '@tabler/icons-react';

const WelcomePage = ({ onLoad }) => {
    const [loading, setLoading] = useState(true);
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        // Simulate loading progress
        const interval = setInterval(() => {
            setProgress(prev => {
                if (prev >= 100) {
                    clearInterval(interval);
                    setLoading(false);
                    if (onLoad) onLoad();
                    return 100;
                }
                return prev + Math.random() * 20;
            });
        }, 100);

        return () => clearInterval(interval);
    }, [onLoad]);

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                <div className="text-center">
                    <div className="w-16 h-16 mx-auto mb-4">
                        <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600"></div>
                    </div>
                    <p className="text-gray-600 dark:text-gray-400">Loading...</p>
                    <div className="w-48 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mx-auto mt-4">
                        <div 
                            className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style={{ width: `${progress}%` }}
                        ></div>
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {Math.round(progress)}%
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-white dark:bg-gray-900">
            {/* Header */}
            <header className="bg-white dark:bg-gray-800 shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-16">
                        <div className="flex items-center space-x-2">
                            <IconRocket className="w-8 h-8 text-blue-600" />
                            <span className="text-xl font-bold text-gray-900 dark:text-white">
                                Pantheon
                            </span>
                        </div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            Laravel + React + TailwindCSS
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="text-center">
                    <h1 className="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-8">
                        Hello World!
                    </h1>
                    
                    <p className="text-xl text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                        Welcome to Pantheon Template Service - A modern Laravel application with React frontend.
                    </p>

                    {/* Tech Stack */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                        <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <div className="w-12 h-12 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <IconBrandLaravel className="w-6 h-6 text-red-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Laravel</h3>
                            <p className="text-gray-600 dark:text-gray-400 text-sm">
                                Modern PHP framework with elegant syntax
                            </p>
                        </div>

                        <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <div className="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <IconBrandReact className="w-6 h-6 text-blue-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">React</h3>
                            <p className="text-gray-600 dark:text-gray-400 text-sm">
                                JavaScript library for building user interfaces
                            </p>
                        </div>

                        <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                            <div className="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <IconBrandTailwind className="w-6 h-6 text-cyan-500" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">TailwindCSS</h3>
                            <p className="text-gray-600 dark:text-gray-400 text-sm">
                                Utility-first CSS framework
                            </p>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <button
                            onClick={() => window.location.href = '/login'}
                            className="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200"
                        >
                            Get Started
                        </button>
                        <button
                            onClick={() => window.location.href = '/api/v1/health'}
                            className="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-8 py-3 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200"
                        >
                            View API
                        </button>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="bg-gray-50 dark:bg-gray-800 mt-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="text-center text-gray-500 dark:text-gray-400">
                        <p>&copy; 2025 {window.Laravel?.appName || 'Pantheon Template Service'}. Built with Laravel, React, and TailwindCSS.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default WelcomePage;