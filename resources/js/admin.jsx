import React from 'react';
import ReactDOM from 'react-dom/client';
import HealthDashboard from './components/HealthDashboard';

// Main Admin Dashboard Component
const AdminDashboard = () => {
    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <HealthDashboard />
            </div>
        </div>
    );
};

// Render the admin dashboard
const root = ReactDOM.createRoot(document.getElementById('admin-dashboard'));
root.render(<AdminDashboard />);
