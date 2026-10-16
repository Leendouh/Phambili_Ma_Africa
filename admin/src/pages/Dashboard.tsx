import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { collection, onSnapshot, query } from 'firebase/firestore';
import { db } from '../firebase';
import type { ServiceRequest } from '../types';

export default function Dashboard() {
  const { userProfile, logout } = useAuth();
  const navigate = useNavigate();
  const [counts, setCounts] = useState({
    pending: 0,
    approved: 0,
    inProgress: 0,
    completed: 0,
    rejected: 0,
    total: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const q = query(collection(db, 'requests'));
    
    const unsubscribe = onSnapshot(q, (snapshot) => {
      const requests: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        requests.push({ id: doc.id, ...doc.data() } as ServiceRequest);
      });

      setCounts({
        pending: requests.filter((r) => r.status === 'Pending').length,
        approved: requests.filter((r) => r.status === 'Approved').length,
        inProgress: requests.filter((r) => r.status === 'In Progress').length,
        completed: requests.filter((r) => r.status === 'Completed').length,
        rejected: requests.filter((r) => r.status === 'Rejected').length,
        total: requests.length,
      });
      setLoading(false);
    }, (error) => {
      console.error('Error fetching requests:', error);
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const StatCard = ({ title, count, color }: { title: string; count: number; color: string }) => (
    <div className={`${color} rounded-lg p-6 text-white`}>
      <h3 className="text-lg font-semibold mb-2">{title}</h3>
      <p className="text-4xl font-bold">{count}</p>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-gray-600">Loading...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <header className="bg-green-700 text-white shadow-md">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">Phambili Ma Africa</h1>
            <p className="text-green-100 text-sm">Admin Dashboard</p>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm">
              Welcome, {userProfile?.firstName} {userProfile?.lastName}
            </span>
            <button
              onClick={handleLogout}
              className="bg-green-600 hover:bg-green-800 px-4 py-2 rounded-md transition-colors"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      {/* Navigation */}
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex space-x-8">
            <button
              onClick={() => navigate('/dashboard')}
              className="py-4 px-2 border-b-2 border-green-700 text-green-700 font-medium"
            >
              Dashboard
            </button>
            <button
              onClick={() => navigate('/requests')}
              className="py-4 px-2 border-b-2 border-transparent text-gray-600 hover:text-green-700 font-medium"
            >
              Requests
            </button>
            <button
              onClick={() => navigate('/employees')}
              className="py-4 px-2 border-b-2 border-transparent text-gray-600 hover:text-green-700 font-medium"
            >
              Employees
            </button>
            <button
              onClick={() => navigate('/services')}
              className="py-4 px-2 border-b-2 border-transparent text-gray-600 hover:text-green-700 font-medium"
            >
              Services
            </button>
            <button
              onClick={() => navigate('/customers')}
              className="py-4 px-2 border-b-2 border-transparent text-gray-600 hover:text-green-700 font-medium"
            >
              Customers
            </button>
            <button
              onClick={() => navigate('/reports')}
              className="py-4 px-2 border-b-2 border-transparent text-gray-600 hover:text-green-700 font-medium"
            >
              Reports
            </button>
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6">Request Overview</h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <StatCard title="Pending" count={counts.pending} color="bg-orange-500" />
          <StatCard title="Approved" count={counts.approved} color="bg-blue-500" />
          <StatCard title="In Progress" count={counts.inProgress} color="bg-purple-500" />
          <StatCard title="Completed" count={counts.completed} color="bg-green-500" />
          <StatCard title="Rejected" count={counts.rejected} color="bg-red-500" />
          <StatCard title="Total Requests" count={counts.total} color="bg-gray-600" />
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
              onClick={() => navigate('/requests')}
              className="bg-green-700 text-white py-3 px-4 rounded-md hover:bg-green-800 transition-colors"
            >
              Manage Requests
            </button>
            <button
              onClick={() => navigate('/employees')}
              className="bg-green-700 text-white py-3 px-4 rounded-md hover:bg-green-800 transition-colors"
            >
              Manage Employees
            </button>
            <button
              onClick={() => navigate('/reports')}
              className="bg-green-700 text-white py-3 px-4 rounded-md hover:bg-green-800 transition-colors"
            >
              View Reports
            </button>
          </div>
        </div>
      </main>
    </div>
  );
}
