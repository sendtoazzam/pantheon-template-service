import React from 'react';
import { createRoot } from 'react-dom/client';
import WelcomePage from './components/WelcomePage';

// Hide loading spinner
const hideLoadingSpinner = () => {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
};

// Initialize React app
const container = document.getElementById('welcome-app');
if (container) {
    const root = createRoot(container);
    root.render(<WelcomePage onLoad={hideLoadingSpinner} />);
}
