import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/App';
import '../css/app.css';

// Import SweetAlert2
import Swal from 'sweetalert2';

// Make SweetAlert2 available globally
window.Swal = Swal;

const container = document.getElementById('app');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
