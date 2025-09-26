import './bootstrap';
import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import StatusPage from './components/StatusPage';

if (document.getElementById('status-root')) {
    createRoot(document.getElementById('status-root')).render(
        <React.StrictMode>
            <StatusPage />
        </React.StrictMode>
    );
}
