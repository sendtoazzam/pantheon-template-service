import React, { useState, useEffect } from 'react';

const App = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Fetch data from Laravel API
        fetch('/api/health')
            .then(response => response.json())
            .then(data => {
                setData(data);
                setLoading(false);
            })
            .catch(error => {
                console.error('Error:', error);
                setLoading(false);
            });
    }, []);

    const showAlert = () => {
        window.Swal.fire({
            title: 'Hello from Pantheon Template Service!',
            text: 'This is a SweetAlert2 notification.',
            icon: 'success',
            confirmButtonText: 'Cool!'
        });
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-100 flex items-center justify-center">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500 mx-auto"></div>
                    <p className="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-100">
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-4xl mx-auto">
                    <div className="bg-white rounded-lg shadow-lg p-8">
                        <h1 className="text-4xl font-bold text-gray-800 mb-6 text-center">
                            Pantheon Template Service
                        </h1>
                        
                        <div className="text-center mb-8">
                            <p className="text-lg text-gray-600 mb-4">
                                Laravel API + React.js + TailwindCSS + Spatie + SweetAlert2
                            </p>
                            
                            <button
                                onClick={showAlert}
                                className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200"
                            >
                                Show SweetAlert
                            </button>
                        </div>

                        {data && (
                            <div className="bg-gray-50 rounded-lg p-6">
                                <h2 className="text-2xl font-semibold text-gray-700 mb-4">API Response:</h2>
                                <pre className="bg-gray-800 text-green-400 p-4 rounded overflow-x-auto">
                                    {JSON.stringify(data, null, 2)}
                                </pre>
                            </div>
                        )}

                        <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="bg-blue-50 p-6 rounded-lg">
                                <h3 className="text-xl font-semibold text-blue-800 mb-2">Laravel API</h3>
                                <p className="text-blue-600">Backend API with Spatie packages for permissions, activity logging, and media management.</p>
                            </div>
                            
                            <div className="bg-green-50 p-6 rounded-lg">
                                <h3 className="text-xl font-semibold text-green-800 mb-2">React.js Frontend</h3>
                                <p className="text-green-600">Modern React frontend with component-based architecture and state management.</p>
                            </div>
                            
                            <div className="bg-purple-50 p-6 rounded-lg">
                                <h3 className="text-xl font-semibold text-purple-800 mb-2">TailwindCSS</h3>
                                <p className="text-purple-600">Utility-first CSS framework for rapid UI development and responsive design.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default App;
